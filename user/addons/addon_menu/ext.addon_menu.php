<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
    This file is part of Add-on Menu add-on for ExpressionEngine.

    Add-on Menu is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Add-on Menu is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    Read the terms of the GNU General Public License
    at <http://www.gnu.org/licenses/>.
    
    Copyright 2016 Derek Hogue - http://amphibian.info
*/

class Addon_menu_ext {

	var $settings = array();
	var $version = '1.0.1';
	

	function __construct($settings = '')
	{
		$this->settings = $settings;
	}
	
	
	function cp_js_end()
	{
		ee()->lang->loadfile('addons');
		$assigned_modules = ee('Model')->get('MemberGroup', ee()->session->userdata('group_id'))->first()->AssignedModules->pluck('module_id');

		$label = (!empty($this->settings['menu_label'])) ? trim($this->settings['menu_label']) : 'Add-ons';
		$markup = '<li><a href="" class="has-sub">'.$label.'</a><div class="sub-menu"><ul>';
				
		$addons = array();
		$all_addons = ee('Addon')->all();
		
		foreach($all_addons as $name => $info)
		{
			$info = ee('Addon')->get($name);
			
			/*
				Bail if we're ignoring this add-on	
			*/
			if(!empty($this->settings['exclude']) && in_array($name, $this->settings['exclude']))
			{
				continue;
			}
			
			
			/*
				We care about add-ons which have settings and are not "built-in"	
			*/
			if($info->isInstalled() && $info->get('settings_exist') && empty($info->get('built_in')))
			{
				/*
					Super Admins see everything	
				*/
				if(ee()->session->userdata('group_id') == 1)
				{
					$addons[$info->getName()] = ee('CP/URL')->make('addons/settings/' . $name);
				}
				/*
					Otherwise if it's a module check for access permissions	
				*/
				elseif($info->hasModule())
				{
					$module = ee('Model')->get('Module')
						->filter('module_name', $name)
						->first();
					if($module)
					{
						if(isset($module->module_id) && in_array($module->module_id, $assigned_modules))
						{
							$addons[$info->getName()] = ee('CP/URL')->make('addons/settings/' . $name);
						}
					}
				}
			}
		}
		
		if(!empty($addons))
		{
			ksort($addons);
			if(!empty($this->settings['include_manager_link']) && $this->settings['include_manager_link'] == 'y')
			{
				$addons[lang('addon_manager')] = ee('CP/URL')->make('addons');
			}
			$i = 1;
			$total = count($addons);
			foreach($addons as $name => $url)
			{
				$class = ($i == $total) ? 'last' : '';
				$markup .= '<li class="'.$class.'"><a href="'.$url.'">'.$name.'</a></li>';
				$i++;
			}
			$markup .= '</ul></div></li>';
			$js = "$('.author-menu').append('$markup');";
			return $js;
		}
	}

	
	function settings()
	{
		$addons = array();
	    $all_addons = ee('Addon')->all();
	    foreach($all_addons as $name => $info)
	    {
		    $info = ee('Addon')->get($name);
		    if($info->isInstalled() && $info->get('settings_exist') && empty($info->get('built_in')))
		    {
			    $addons[$name] = $info->getName();		    
		    }
	    }
		ksort($addons);

	    $settings = array();
	    $settings['menu_label'] = array('i', '', 'Add-ons');
	    $settings['include_manager_link'] = array('r', array('y' => 'yes', 'n' => 'No'), 'n');
	    $settings['exclude'] = array('ms', $addons, (!empty($this->settings['exclude'])) ? $this->settings['exclude'] : '');
	    return $settings;
	}
	

	function activate_extension()
	{		
		$data = array(
			'class'		=> __CLASS__,
			'method'	=> 'cp_js_end',
			'hook'		=> 'cp_js_end',
			'settings'	=> serialize($this->settings),
			'version'	=> $this->version,
			'enabled'	=> 'y'
		);

		ee()->db->insert('extensions', $data);			
	}	
	

	function disable_extension()
	{
		ee()->db->where('class', __CLASS__);
		ee()->db->delete('extensions');
	}


	function update_extension($version = '')
	{
		if(version_compare($version, $this->version) === 0)
		{
			return FALSE;
		}
		return TRUE;		
	}	
	

}