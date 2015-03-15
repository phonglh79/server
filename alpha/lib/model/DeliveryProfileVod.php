<?php

/**
 * This class is an abstract implementation for VOD delivery profiles  
 */
abstract class DeliveryProfileVod extends DeliveryProfile {
	
	/**
	 * @var array
	 */
	protected $preferredFlavor = null;
	
	/** -------------------
	 * Functionality 
	 * --------------------*/
	
	/**
	 * @param flavorAsset $flavorAsset
	 * @return string representing the basic url.
	 */
	protected function getBaseUrl(flavorAsset $flavorAsset) {
		$partnerId = $flavorAsset->getPartnerId();
		$subpId = $flavorAsset->getentry()->getSubpId();
		$partnerPath = myPartnerUtils::getUrlForPartner($partnerId, $subpId);
		$flavorAssetId = $flavorAsset->getId();
		$versionString = $this->getFlavorVersionString($flavorAsset);
		$urlParams = $this->params->getUrlParams();
		
		$url = "$partnerPath/serveFlavor/entryId/".$flavorAsset->getEntryId()."{$versionString}/flavorId/$flavorAssetId" . $urlParams;
		return $url;
	}
	
	/**
	 * @param flavorAsset $flavorAsset
	 * @return string representing the version string
	 */
	protected function getFlavorVersionString(flavorAsset $flavorAsset)
	{
		$entry = $flavorAsset->getentry();
		$partner = $entry->getPartner();
	
		$flavorAssetVersion = $flavorAsset->getVersion();
		$partnerFlavorVersion = $partner->getCacheFlavorVersion();
		$entryFlavorVersion = $entry->getCacheFlavorVersion();
	
		return (!$flavorAssetVersion ? '' : "/v/$flavorAssetVersion").
		($partnerFlavorVersion ? "/pv/$partnerFlavorVersion" : '') .
		($entryFlavorVersion ? "/ev/$entryFlavorVersion" : '');
	}
	
	protected function doGetFlavorAssetUrl(flavorAsset $flavorAsset)
	{
		$url = $this->getBaseUrl($flavorAsset);
		if($this->params->getClipTo())
			$url .= "/clipTo/" . $this->params->getClipTo();
		return $url;
	}
	
	/**
	 * @param thumbAsset $thumbAsset
	 * @return string
	 */
	protected function doGetThumbnailAssetUrl(thumbAsset $thumbAsset)
	{
		$thumbAssetId = $thumbAsset->getId();
		$partnerId = $thumbAsset->getPartnerId();
		$url = "/api_v3/service/thumbAsset/action/serve/partnerId/$partnerId/thumbAssetId/$thumbAssetId";
	
		return $url;
	}
	
	public function getAssetUrl(asset $asset, $tokenizeUrl = true)
	{
		$url = null;
	
		if($asset instanceof thumbAsset)
			$url = $this->doGetThumbnailAssetUrl($asset);
	
		if($asset instanceof flavorAsset)
		{
			
			$url = $this->doGetFlavorAssetUrl($asset);

			$url = str_replace('\\', '/', $url);
			if ($tokenizeUrl)
			{
				$tokenizer = $this->getTokenizer();
				if ($tokenizer)
				{
					$url = $tokenizer->tokenizeSingleUrl($url);
					kApiCache::disableCache();
				}
			}
		}
	
		return $url;
	}
	
	public function getFullAssetUrl(asset $asset, $tokenizeUrl = true) {
		$assetUrl = $this->getAssetUrl($asset, $tokenizeUrl);
		$hostName = $this->getHostName();
		
		$partner = PartnerPeer::retrieveByPK($asset->getPartnerId());
		if($partner)
		{
			$defaultDeliveryCode = $partner->getDefaultDeliveryCode(); 
			if($defaultDeliveryCode !== false)
				$hostName = str_replace("{deliveryCode}", $defaultDeliveryCode, $hostName);
		}
		
		return $hostName . $assetUrl;
	}
	
	public function getDynamicAttribues()
	{
		return $this->params;
	}
	
	public function setDynamicAttribues(DeliveryProfileDynamicAttributes $attributes)
	{
		$this->params = $attributes;
	}
	
	protected function addSeekFromBytes($flavorAsset, $url, $prefix) {
		$syncKey = $flavorAsset->getSyncKey(flavorAsset::FILE_SYNC_FLAVOR_ASSET_SUB_TYPE_ASSET);
		$seekFromBytes = $this->getSeekFromBytes(kFileSyncUtils::getLocalFilePathForKey($syncKey));
		if($seekFromBytes)
			$url .= '&' . $prefix . '=' . $seekFromBytes;
		return $url;
	}
	
	/**
	 * @return int
	 */
	protected function getSeekFromBytes($path)
	{
		if($this->params->getSeekFromTime() <= 0)
			return null;
	
		$flvWrapper = new myFlvHandler($path);
		if(!$flvWrapper->isFlv())
			return null;
	
		$audioOnly = false;
		if($flvWrapper->getFirstVideoTimestamp() < 0 )
			$audioOnly = true;
	
		list ( $bytes , $duration ,$firstTagByte , $toByte ) = $flvWrapper->clip(0, -1, $audioOnly);
		list ( $bytes , $duration ,$fromByte , $toByte, $seekFromTimestamp ) = $flvWrapper->clip($this->params->getSeekFromTime(), -1, $audioOnly);
		$seekFromBytes = myFlvHandler::FLV_HEADER_SIZE + $flvWrapper->getMetadataSize($audioOnly) + $fromByte - $firstTagByte;
	
		return $seekFromBytes;
	}
	
	public function getFullFileSyncUrl(FileSync $fileSync, $tokenizeUrl = true) {
		$url = $this->getFileSyncUrl($fileSync, $tokenizeUrl);
		return $this->getHostName() . $url;
	}
	
	/**
	 * @param FileSync $fileSync
	 * @param bool $tokenizeUrl
	 * @return string
	 */
	public function getFileSyncUrl(FileSync $fileSync, $tokenizeUrl = true)
	{
		$fileSync = kFileSyncUtils::resolve($fileSync);
		$url = $this->doGetFileSyncUrl($fileSync);
		$url = str_replace('\\', '/', $url);
		if ($tokenizeUrl)
		{
			$tokenizer = $this->getTokenizer();
			if ($tokenizer)
			{
				$url = $tokenizer->tokenizeSingleUrl($url);
				kApiCache::disableCache();
			}
		}
		return $url;
	}
	
	/**
	 * @param FileSync $fileSync
	 * @return string
	 */
	protected function doGetFileSyncUrl(FileSync $fileSync)
	{
		$url = $fileSync->getFilePath();
		return $url;
	}
	
	/** -------------------
	 * 		Serve
	 * --------------------*/
	public function serve() {
		$flavors = $this->buildFlavors();
		return $this->retrieveRenderer($flavors);
	}
	
	/**
	 * @param bool $oneOnly
	 * @return array
	 */
	protected function buildHttpFlavorsArray()
	{
		$flavors = array();
		foreach($this->params->getflavorAssets() as $flavorAsset)
		{
			/* @var $flavorAsset flavorAsset */
			$httpUrl = $this->getFlavorHttpUrl($flavorAsset);
			if ($httpUrl)
				$flavors[] = $httpUrl;
		}
		return $flavors;
	}
	
	/**
	 * @param flavorAsset $flavorAsset
	 * @return array
	 */
	protected function getFlavorHttpUrl(flavorAsset $flavorAsset)
	{
		if ($this->params->getStorageId()) {
			return $this->getExternalStorageUrl($flavorAsset);
		}
			
		$this->initDeliveryDynamicAttributes(null, $flavorAsset);
		$url = $this->getAssetUrl($flavorAsset, false);
		if ($this->params->getFormat() == PlaybackProtocol::RTSP)
		{
			// the host was already added by the url manager
			return $this->getFlavorAssetInfo($url, '', $flavorAsset);
		}
	
		$urlPrefix = '';
		if (strpos($url, "/") === 0)
		{
			$flavorSizeKB = $flavorAsset->getSize();
			if ($flavorSizeKB > kConf::get("max_file_size_downloadable_from_cdn_in_KB"))
				$urlPrefix = requestUtils::getRequestHost();
			else
				$urlPrefix = $this->url;
		}
	
		$urlPrefix = preg_replace('/^https?:\/\//', '', $urlPrefix);
		$url = preg_replace('/^https?:\/\//', '', $url);
	
		if ($urlPrefix)
		{
			$urlPrefix = $this->params->getMediaProtocol() . '://' . $urlPrefix;
			$urlPrefix = rtrim($urlPrefix, "/") . "/";
		}
		else
		{
			$url = $this->params->getMediaProtocol() . '://' . $url;
		}
	
		$url = ltrim($url, "/");
	
		return $this->getFlavorAssetInfo($url, $urlPrefix, $flavorAsset);
	}
	
	/**
	 * @param flavorAsset $flavorAsset
	 * @param FileSyncKey $key
	 * @return array
	 */
	protected function getExternalStorageUrl(flavorAsset $flavorAsset)
	{
		$remoteFileSyncs = $this->params->getRemoteFileSyncs();
		$fileSync = $remoteFileSyncs[$flavorAsset->getId()];
	
		$this->initDeliveryDynamicAttributes($fileSync, $flavorAsset);
		$url = $this->getFileSyncUrl($fileSync, false);
		$url = ltrim($url, "/");
	
		$urlPrefix = '';
		if (strpos($url, "://") === false) {
			$urlPrefix = $this->getUrl();
		}
	
		return $this->getFlavorAssetInfo($url, $urlPrefix, $flavorAsset);
	}
	
	/**
	 *
	 * Private function which compares 2 flavors in order to sort an array.
	 * If a flavor's width and height parameters are equal to 0, it is
	 * automatically moved down the list so the player will not start playing it by default.
	 * @param array $flavor1
	 * @param array $flavor2
	 */
	protected function flavorCmpFunction ($flavor1, $flavor2)
	{
		// move the audio flavors to the end
		$isAudio1 = $flavor1['height'] == 0 && $flavor1['width'] == 0;
		$isAudio2 = $flavor2['height'] == 0 && $flavor2['width'] == 0;
		
		if ($isAudio1 != $isAudio2)
		{
			if ($isAudio1)
			{
				return 1;
			}
			else 
			{
				return -1;
			}
		}
	
		// if a preferred bitrate was defined place it first
		if ($this->preferredFlavor == $flavor2)
		{
			return 1;
		}
		if ($this->preferredFlavor == $flavor1)
		{
			return -1;
		}
	
		// sort the flavors in ascending bitrate order
		if ($flavor1['bitrate'] >= $flavor2['bitrate'])
		{
			return 1;
		}
	
		return -1;
	}
	
	/**
	 * @param array $flavors
	 * @return array
	 */
	protected function sortFlavors($flavors)
	{
		$this->preferredFlavor = null;
		$minBitrateDiff = PHP_INT_MAX;
	
		if (!is_null($this->params->getPreferredBitrate()))
		{
			foreach ($flavors as $flavor)
			{
				if ($flavor['height'] == 0 && $flavor['width'] == 0)
					continue;		// audio flavor
	
				$bitrateDiff = abs($flavor['bitrate'] - $this->params->getPreferredBitrate());
				if (!$this->preferredFlavor || $bitrateDiff < $minBitrateDiff)
				{
					$this->preferredFlavor = $flavor;
					$minBitrateDiff = $bitrateDiff;
				}
			}
		}
	
		uasort($flavors, array($this,'flavorCmpFunction'));
	
		return $flavors;
	}
}

