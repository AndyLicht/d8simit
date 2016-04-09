<?php
 
namespace Drupal\simmanager\Controller;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

 
class AdminForm extends ConfigFormBase 
{
    
    public function getFormId() 
    {
        return 'simmanager_settings_form';
    }
    
    protected function getEditableConfigNames() 
    {
        return ['simmanager.settings',];  
    }
    
    public function buildForm(array $form, FormStateInterface $form_state) 
    {
        $config = $this->config('simmanager.settings');
        $form['description'] = array(
            '#markup' => t('here you can manage the most settings of the SimManage Module, database and drupal parameters you have to define in the java-xml.'),
        );
        $form['advanced'] = array(
            '#type' => 'vertical_tabs',
            '#title' => t('Settings'),
        );
        $form['base'] = array(
            '#type' => 'fieldset',
            '#title' => t('Base Settings'),
            '#group' => 'advanced',
        );
        $form['base']['basePath'] = array(
            '#type' => 'textfield',
            '#default_value' => $config->get('basePath'),
            '#required' =>TRUE,
            '#title' => t('Drupal Base Path of WeatherAPI'),
        );
        
        $form['rest'] = array(
            '#type' => 'fieldset',
            '#title' => t('REST Settings'),
            '#group' => 'advanced',
        );
        $form['rest']['weathernodePath'] = array(
            '#type' => 'textfield',
            '#value' => $config->get('weathernodePath'),
            '#required' =>TRUE,
            '#title' => t('Path of the weathernodes'),
        );
        $form['rest']['weatherapiPath']= array(
            '#type' => 'textfield',
            '#value' => $config->get('weatherapiPath'),
            '#required' =>TRUE,
            '#title' => t('Drupal Base Path of WeatherAPI'),
        );
        $form['rest']['entityNodePOST'] = array(
            '#type' => 'textfield',
            '#value' => $config->get('entityNodePOST'),
            '#required' =>TRUE,
            '#title' => t('Drupal Base Path of POST'),
        );
        $form['rest']['entityNodePATCH'] = array(
            '#type' => 'textfield',
            '#value' => $config->get('entityNodePATCH'),
            '#required' =>TRUE,
            '#title' => t('Drupal Base Path of PATCH'),
        );
        $form['rest']['entityNodeDELETE'] = array(
            '#type' => 'textfield',
            '#value' => $config->get('entityNodeDELETE'),
            '#required' =>TRUE,
            '#title' => t('Drupal Base Path of DELETE'),
        );
        $form['rest']['entityNodeGET'] = array(
            '#type' => 'textfield',
            '#value' => $config->get('entityNodeGET'),
            '#required' =>TRUE,
            '#title' => t('Drupal Base Path of GET'),
        );
        $form['control'] = array(
            '#type' => 'fieldset',
            '#title' => t('Control'),
            '#group' => 'advanced',
        );
        $form['control']['voronoi'] = array(
            '#type' => 'submit',
            '#title' => t('Control'),
            '#value' => t('build voronoi'),
        );
        $form['control'] = array(
            '#type' => 'fieldset',
            '#title' => t('Control'),
            '#group' => 'advanced',
        );
        $form['control']['markup'] = array(
            '#markup' => t('Controlpanel for creating default WeatherAPIs, to build the voronoi manuelly or to start manuel getting the weather of the different weatherapis.'),
        );
        if($config->get('voronoibuild') == FALSE)
        {
            $form['control']['voronoi'] = array(
                '#type' => 'submit',
                '#submit' => array('::voronoi_start'),
                '#value' => t('build voronoi'),
            );
        }
        if($config->get('DWDNode') == null)
        {
            $form['control']['createDWD'] = array(
                '#type' => 'submit',
                '#submit' => array('::createDWDNode'),
                '#value' => t('build DWD API'),
            ); 
        }
        if($config->get('DWDgetweatherbuild') == FALSE)
        {
            $form['control']['dwdcontrol'] = array(
                '#type' => 'submit',
                '#value' => t('dwd weather'),
                '#submit' => array('::getDWDWeather'),
            );
        }
        return parent::buildForm($form, $form_state);
    }

    public function getDWDWeather()
    {
        dsm('get Weather');
        $config = $this->config('simmanager.settings');
        $config->set('DWDgetweatherbuild',TRUE)
                ->save();
    }
    
    public function voronoi_start()
    {
        $config = $this->config('simmanager.settings');
        $config->set('voronoibuild',TRUE)
                ->save();
        exec("java -jar C:\Users\Tim\Documents\NetBeansProjects\weatherManager\dist\weatherManager.jar voronoi", $output);
    }
    
    public function submitForm(array &$form, FormStateInterface $form_state)
    {   
        //$config = \Drupal::service('config.factory')->getEditable('simmanager.settings')
        $config = $this->config('simmanager.settings')
            ->set('basePath',$form_state->getValue('basePath'))
            ->set('weathernodePath',$form_state->getValue('weathernodePath'))
            ->set('weatherapiPath',$form_state->getValue('weatherapiPath'))
            ->set('entityNodePOST',$form_state->getValue('entityNodePOST'))
            ->set('entityNodeDELETE',$form_state->getValue('entityNodeDELETE'))
            ->set('entityNodePATCH',$form_state->getValue('entityNodePATCH'))
            ->set('entityNodeGET',$form_state->getValue('entityNodeGET'))
            ->save();
        parent::submitForm($form, $form_state);
    }
    
    public function validateForm(array &$form, FormStateInterface $form_state) 
    {
       
    }
    
    public function createDWDNode()
    {
        $new_weatherapi_values = array();
        $new_weatherapi_values['type'] = 'sim_weatherapi';
        $new_weatherapi_values['title'] = 'Deutscher Wetterdienst FTP';
        $new_weatherapi_values['body'] = 'Vom DWD werden die Daten über ein FTP-Zugang tgl. aktualisiert.';
        $new_weatherapi_values['field_simshortname'] = 'dwd';
        $new_weatherapi_values['field_simurls'][0] = 'temp=>ftp://ftp-cdc.dwd.de/pub/CDC/observations_germany/climate/hourly/air_temperature/recent/';
        $new_weatherapi_values['field_simurls'][1] = 'preci=>ftp://ftp-cdc.dwd.de/pub/CDC/observations_germany/climate/hourly/precipitation/recent/';
        $new_weatherapi_values['field_oldweatherview'] = 'rest/simmanager/DWDoldweather';
        $new_weatherapi_values['field_tovoronoi'] = 'rest/simmanager/tovoronoi';
        $new_weatherapi_values['field_voronoi'] = 'rest/simmanager/voronoi';
        $new_weatherapi = entity_create('node', $new_weatherapi_values);
        $new_weatherapi->save();
        
        $config = \Drupal::service('config.factory')->getEditable('simmanager.settings');
        $config->set('DWDNode',$new_weatherapi->id());
        $config->save();
    }

}