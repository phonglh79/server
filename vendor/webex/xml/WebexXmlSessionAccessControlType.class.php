<?php
require_once(__DIR__ . '/WebexXmlRequestType.class.php');

class WebexXmlSessionAccessControlType extends WebexXmlRequestType
{
	/**
	 *
	 * @var WebexXmlSessListingType
	 */
	protected $listing;
	
	/**
	 *
	 * @var WebexXml
	 */
	protected $sessionPassword;
	
	/**
	 *
	 * @var boolean
	 */
	protected $enforcePassword;
	
	/* (non-PHPdoc)
	 * @see WebexXmlObject::getAttributeType()
	 */
	protected function getAttributeType($attributeName)
	{
		switch ($attributeName)
		{
			case 'listing':
				return 'WebexXmlSessListingType';
	
			case 'sessionPassword':
				return 'WebexXml';
	
			case 'enforcePassword':
				return 'boolean';
	
		}
		
		return parent::getAttributeType($attributeName);
	}
	
	/* (non-PHPdoc)
	 * @see WebexXmlRequestType::getMembers()
	 */
	public function getMembers()
	{
		return array(
			'listing',
			'sessionPassword',
			'enforcePassword',
		);
	}
	
	/* (non-PHPdoc)
	 * @see WebexXmlRequestType::getRequiredMembers()
	 */
	protected function getRequiredMembers()
	{
		return array(
		);
	}
	
	/* (non-PHPdoc)
	 * @see WebexXmlRequestType::getXmlNodeName()
	 */
	protected function getXmlNodeName()
	{
		return 'accessControlType';
	}
	
	/**
	 * @param WebexXmlSessListingType $listing
	 */
	public function setListing(WebexXmlSessListingType $listing)
	{
		$this->listing = $listing;
	}
	
	/**
	 * @return WebexXmlSessListingType $listing
	 */
	public function getListing()
	{
		return $this->listing;
	}
	
	/**
	 * @param WebexXml $sessionPassword
	 */
	public function setSessionPassword(WebexXml $sessionPassword)
	{
		$this->sessionPassword = $sessionPassword;
	}
	
	/**
	 * @return WebexXml $sessionPassword
	 */
	public function getSessionPassword()
	{
		return $this->sessionPassword;
	}
	
	/**
	 * @param boolean $enforcePassword
	 */
	public function setEnforcePassword($enforcePassword)
	{
		$this->enforcePassword = $enforcePassword;
	}
	
	/**
	 * @return boolean $enforcePassword
	 */
	public function getEnforcePassword()
	{
		return $this->enforcePassword;
	}
	
}
		
