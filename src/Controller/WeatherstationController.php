<?php

namespace Drupal\simmanager\Controller;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;


class WeatherstationController extends FormBase 
{
    protected $step = 1;
    protected $name = "";
    protected $description = "";
    protected $x;
    protected $y;
    protected $apiname;
    protected $apiid;
    protected $api;
    public $config ;
    protected $table = [
        'fields' => [
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
        return 'set_weatherstation_form';
    }

    /**
    * {@inheritdoc}
    */
    public function buildForm(array $form, FormStateInterface $form_state) 
    {
        //$form = parent::buildForm($form, $form_state);
        $this->config = \Drupal::config('simmanager.settings');
        
        if($this->step == 1) 
        {
            $query = \Drupal::entityQuery('node')
                ->condition('status', 1)
                ->condition('type', 'sim_weatherapi');
            $nids = $query->execute();
            
            $available_weatherstations = array();
            foreach($nids as $nid)
            {
                $node = node_load($nid);
                $available_weatherstations[$nid] = t($node->getTitle());
            }
            //Forms
            //Title
            $form['title'] = array(
                '#type' => 'textfield',
                '#required' =>TRUE,
                '#title' => t('Name'),
            );
            //Description
            $form['description'] = array(
                '#type' => 'textarea',
                '#required' =>TRUE,
                '#title' => t('Description'),
            );
            $form['weatherstationapi'] = array(
                '#type' => 'radios',
                '#options' => $available_weatherstations,
                '#required' =>TRUE,
                '#title' => t('What type of weatherstation you will add?'),
            );
        }
        
        if($this->step == 2) 
        {
            $form = [];
            $form['api'] = [
                '#markup' => 'API Setup',
            ];
            $form['form_simname'] = [
                '#type' => 'textfield',
                '#required' =>TRUE,
                '#title' => t('API Name'),
            ];
            $form['form_simid'] = [
                '#type' => 'textfield',
                '#required' =>TRUE,
                '#title' => t('API ID'),
            ];
            $form['form_simxcoor'] = [
                '#type' => 'textfield',
                '#required' =>TRUE,
                '#title' => t('API Longitude'),
            ];
            $form['form_simycoor'] = [
                '#type' => 'textfield',
                '#required' =>TRUE,
                '#title' => t('API Latitude'),
            ];
        }
        //Preview
        if($this->step == 3) 
        {
            $form['api'] = [
                '#markup' => 'Preview',
            ];
        }
        
        if($this->step < 3) 
        {
            $button_label = $this->t('Next');
        }
        else 
        {
            $button_label = $this->t('Create Weatherstation');
        }
        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $button_label,
        ];
        return $form;
    }

  
    public function validateForm(array &$form, FormStateInterface $form_state) 
    {
       
    }

    public function submitForm(array &$form, FormStateInterface $form_state) 
    {
        if($this->step == 1) 
        {
            $this->name = $form_state->getValue('title');
            $this->description = $form_state->getValue('description');
            $this->api = $form_state->getValue('weatherstationapi');
            $form_state->setRebuild();
            $this->step++;
        }
        elseif($this->step == 2) 
        {
            $this->apiname = $form_state->getValue('form_simname');
            $this->apiid = $form_state->getValue('form_simid');
            $this->x = $form_state->getValue('form_simxcoor');
            $this->y = $form_state->getValue('form_simycoor');
            $form_state->setRebuild();
            $this->step++;
        }
        elseif($this->step == 3)
        {
            
            $new_weatherstation_values = array();
            $new_weatherstation_values['type'] = 'sim_weatherstation';
            $new_weatherstation_values['title'] = $this->name;
            $new_weatherstation_values['body'] = $this->description;
            $new_weatherstation_values['field_simapiid'] = $this->apiid;
            $new_weatherstation_values['field_simapiname'] = $this->apiname;
            $new_weatherstation_values['field_simweatherstationid'] = uniqid();
            $new_weatherstation_values['field_simdbtablename'] = 'sim_'.$new_weatherstation_values['field_simweatherstationid'];
            $new_weatherstation_values['field_simpoint'] = 'POINT ('.$this->x.' '.$this->y.')';
            $new_weatherstation_values['field_weatherapi'] = $this->api;
            $new_weatherstation = entity_create('node', $new_weatherstation_values);
            $new_weatherstation->save();
            db_create_table($new_weatherstation_values['field_simdbtablename'],$this->table);
        }
    }
}