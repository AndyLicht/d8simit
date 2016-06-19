<?php
 
namespace Drupal\simmanager\Controller;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

 
class AdminForm extends ConfigFormBase 
{
    protected $table = [
        'fields' => 
        [
            'timestamp' =>['pgsql_type' => 'timestamp with time zone', 'not null' => TRUE],
            'temp' => ['type' => 'float', 'unsigned' => FALSE, 'not null' => FALSE],
            'rain' => ['type' => 'float', 'unsigned' => TRUE, 'not null' => FALSE],
            'snow' => ['type' => 'float', 'unsigned' => TRUE, 'not null' => FALSE],
            'pressure' => ['type' => 'float', 'unsigned' => TRUE, 'not null' => FALSE],
            'humidity' => ['type' => 'float', 'unsigned' => TRUE, 'not null' => FALSE],
            'windspeed' => ['type' => 'float', 'unsigned' => TRUE, 'not null' => FALSE],
            'winddirection' => ['type' => 'float', 'unsigned' => TRUE, 'not null' => FALSE],
            'api' => ['type' => 'text', 'not null' => TRUE],
            'forecast' => ['pgsql_type' => 'boolean', 'not null' => TRUE,'default' => '0'],
        ],
    ];
    
    
    
    
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
            '#default_value' => $config->get('weathernodePath'),
            '#required' =>TRUE,
            '#title' => t('Path of the weathernodes'),
        );
        $form['rest']['weatherapiPath']= array(
            '#type' => 'textfield',
            '#default_value' => $config->get('weatherapiPath'),
            '#required' =>TRUE,
            '#title' => t('Drupal Base Path of WeatherAPI'),
        );
        $form['rest']['entityNodePOST'] = array(
            '#type' => 'textfield',
            '#default_value' => $config->get('entityNodePOST'),
            '#required' =>TRUE,
            '#title' => t('Drupal Base Path of POST'),
        );
        $form['rest']['entityNodePATCH'] = array(
            '#type' => 'textfield',
            '#default_value' => $config->get('entityNodePATCH'),
            '#required' =>TRUE,
            '#title' => t('Drupal Base Path of PATCH'),
        );
        $form['rest']['entityNodeDELETE'] = array(
            '#type' => 'textfield',
            '#default_value' => $config->get('entityNodeDELETE'),
            '#required' =>TRUE,
            '#title' => t('Drupal Base Path of DELETE'),
        );
        $form['rest']['entityNodeGET'] = array(
            '#type' => 'textfield',
            '#default_value' => $config->get('entityNodeGET'),
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
        if($config->get('simlinks') == FALSE)
        {
            $form['control']['simlinks'] = array(
                '#type' => 'submit',
                '#value' => t('create links for Settings'),
                '#submit' => array('::createLinks'),
            );
        }
        if($config->get('simdwdstations') == FALSE)
        {
            $form['control']['simdwdstations'] = array(
                '#type' => 'submit',
                '#value' => t('create DWD Stations'),
                '#submit' => array('::createDWDStations'),
            );
        }
        return parent::buildForm($form, $form_state);
    }
    
    
    public function createDWDStations()
    {
        $string = file_get_contents(getcwd().'/modules/custom/d8simit/dwdstations.txt');
        $string_ = explode("\n",$string);
        $nids = \Drupal::entityQuery('node')
                ->condition('field_simshortname', 'dwd', '=')
                ->execute();
        $apiid;
        if(sizeof($nids) === 1)
        {
            foreach($nids as $key => $value)
            {
                $apiid = $value;
            }
        }
        else
        {
            drupal_set_message('at first install dwd api');
            
        }
        if(isset($apiid))
        {    
            for($i = 2; $i < sizeof($string_); $i++)
            {
                $weatherstation = array(
                    "id"  => str_replace(" ","",substr($string_[$i],0,11)),
                    "x" => str_replace(" ","",substr($string_[$i],47,10)),
                    "y" => str_replace(" ","",substr($string_[$i],57,10)),
                    "title" => trim(substr($string_[$i],67,40)),
                    "body" => str_replace(" ","",substr($string_[$i],108))
                );
                $new_weatherstation_values = array();
                $new_weatherstation_values['type'] = 'sim_weatherstation';
                $new_weatherstation_values['title'] = $weatherstation['title'];
                $new_weatherstation_values['body'] = $weatherstation['body'];
                $new_weatherstation_values['field_simapiid'] = $weatherstation['id'];
                $new_weatherstation_values['field_simapiname'] = 'dwd';
                $new_weatherstation_values['field_simweatherstationid'] = uniqid();
                $new_weatherstation_values['field_simdbtablename'] = 'sim_'.$new_weatherstation_values['field_simweatherstationid'];
                $new_weatherstation_values['field_simpoint'] = 'POINT ('.$weatherstation['x'].' '.$weatherstation['y'].')';
                $new_weatherstation_values['field_weatherapi'] = $apiid;
                $new_weatherstation = entity_create('node', $new_weatherstation_values);
                $new_weatherstation->save();
                db_create_table($new_weatherstation_values['field_simdbtablename'],$this->table);
            }
        }    
    }
    
    public function createLinks()
    {
        MenuLinkContent::create([
            'title' => 'AddWeatherstation',
            'link' => ['uri' => 'internal:/simmanager/addweatherstation'],
            'menu_name' => 'simmanager',
        ])->save();


        MenuLinkContent::create([
            'title' => 'SimManager Settings',
            'link' => ['uri' => 'internal:/simmanager/settings'],
            'menu_name' => 'simmanager',
        ])->save();
        
        $this->config('simmanager.settings')
            ->set('simlinks',TRUE)
            ->save();
    }
    
    public function getDWDWeather()
    {
        $this->config('simmanager.settings')
            ->set('DWDgetweatherbuild',TRUE)
            ->save();
    }
    
    public function voronoi_start()
    {
        $this->config('simmanager.settings')
            ->set('voronoibuild',TRUE)
            ->save();
        exec("java -jar C:\Users\Tim\Documents\NetBeansProjects\weatherManager\dist\weatherManager.jar voronoi", $output);
    }
    
    public function submitForm(array &$form, FormStateInterface $form_state)
    {   
        $this->config('simmanager.settings')
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