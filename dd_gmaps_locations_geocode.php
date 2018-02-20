<?php
/**
 * @package    DD_GMaps_Locations_Geocode
 *
 * @author     HR IT-Solutions Florian Häusler <info@hr-it-solutions.com>
 * @copyright  Copyright (C) 2016 - 2018 Didldu e.K. | HR IT-Solutions
 * @license    http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
 **/

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

/**
 * DD GMaps Locations Plugin
 *
 * @since  Version 1.0.0.0
 */
class PlgSystemDD_GMaps_Locations_GeoCode extends JPlugin
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
		/*
		 * Mod DD GMaps Module -> GeoCode Address
		 * Check if extension save event is on module mod_dd_gmaps_module after save
		 * */
		if ($context == 'com_modules.module' && $table->module == 'mod_dd_gmaps_module')
		{
			$params = json_decode($table->params);

			// If not geohardcode
			if ($params->geohardcode !== '1')
			{
				// Get latitude and longitude
				$latlng = $this->Geocode_Location_To_LatLng($params);

				$params->latitude  = $latlng['latitude'];
				$params->longitude = $latlng['longitude'];

				$table->params = json_encode($params);

				// Save parameters
				$db = JFactory::getDbo();
				$query = $db->getQuery(true);
				$query->update($db->qn('#__modules'))
					->set($db->qn('params') . '=' . $db->q($table->params))
					->where(array($db->qn('id') . '=' . $table->id ));
				$db->setQuery($query);
				$db->execute();
			}

			return true;
		}
		else
		{
			return true;
		}
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

			return true;
		}
		elseif ($output->status == 'ZERO_RESULTS')
		{
			JFactory::getApplication()->enqueueMessage(JText::_('PLG_SYSTEM_DD_GMAPS_LOCATIONS_GEOCODE_API_ALERT_GEOLOCATION_FAILED_ZERO_RESULTS'), 'warning');
		}

		// Build array latitude and longitude
		$latlng = array("latitude"  => $output->results[0]->geometry->location->lat,
						"longitude" => $output->results[0]->geometry->location->lng);

		// Return Array
		return $latlng;
	}
}
