<?php
/**
 * @version    1-1-0-0 // Y-m-d 2017-04-14
 * @author     HR IT-Solutions Florian Häusler https://www.hr-it-solutions.com
 * @copyright  Copyright (C) 2011 - 2017 Didldu e.K. | HR IT-Solutions
 * @license    http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
 **/

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

/**
 * DD GMaps Locations Plugin
 *
 * @since  Version 1.0.0.0
 */
class PlgSystemDD_GMaps_Locations extends JPlugin
{
	protected $app;

	protected $autoloadLanguage = true;

	/**
	 * On extension after save event
	 *
	 * @return  boolean
	 *
	 * @since   Version 1.0.0
	 */
	public function onExtensionAfterSave($context, $table, $isNew)
	{
		// Check if extension save event is on module mod_dd_gmaps_module after save
		if ($context == 'com_modules.module' && $table->module == 'mod_dd_gmaps_module')
		{
			$params = json_decode($table->params);

			// Get latitude and longitude
			$latlng = $this->Geocode_Location_To_LatLng($params);

			$params->latitude  = $latlng['latitude'];
			$params->longitude = $latlng['longitude'];

			$table->params = json_encode($params);

			return true;
		}

		return true;
	}

	/**
	 * Get latitude and longitude by address from Google GeoCode API
	 *
	 * @param   object  $params  the table data which must include 'street' 'zip' 'location' 'federalstate' and 'country'
	 *
	 * @return  array   latitude and longitude
	 *
	 * @since   Version 1.1.0.0
	 */
	protected function Geocode_Location_To_LatLng($params)
	{
		// Get Location Data
		$address = array(
			'street'        => $params->street,
			'zip'           => $params->zip ,
			'location'      => $params->location,
			'country'       => JText::_($params->country) // Convert language string to country name
		);

		// Get API Key if key is set
		$google_api_URL_pram = '';

		if ($params->google_api_key_geocode)
		{
			$google_api_URL_pram    = '&key=' . trim($params->google_api_key_geocode);
		}
		// Prepare Address
		$prepAddr = implode('+', $address);

		// Get Contents and decode
		$geoCode = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($prepAddr) . '&sensor=false' . $google_api_URL_pram);
		$output  = json_decode($geoCode);

		if (@$output->error_message != "") // If Error on API Connection, display error not
		{
			JFactory::getApplication()->enqueueMessage($output->error_message, 'Note');

			return false;
		}

		// Build array latitude and longitude
		$latlng = array("latitude"  => $output->results[0]->geometry->location->lat,
		                "longitude" => $output->results[0]->geometry->location->lng);

		// Return Array
		return $latlng;
	}
}
