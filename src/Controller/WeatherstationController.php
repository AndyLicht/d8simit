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
    protected $dwdname;
    protected $dwdid;
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
        
        dsm($this->step);
        
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
                $available_weatherstations[$node->getTitle()] = t($node->getTitle());
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
            $form['weaterstationapi'] = array(
                '#type' => 'checkboxes',
                '#options' => $available_weatherstations,
                '#required' =>TRUE,
                '#title' => t('What type of weatherstation you will add?'),
            );
        }
        
        if($this->step == 2) 
        {
            dsm('Drinnen');
            $form = [];
            foreach ($form_state->getValue('weaterstationapi') as $key => $value)
            {
                if($value == 'Deutscher Wetterdienst FTP')
                {
                    $form['dwd'] = [
                        '#markup' => 'DWD Setup',
                    ];
                    $form['form_simdwdname'] = [
                        '#type' => 'textfield',
                        '#required' =>TRUE,
                        '#title' => t('DWD Name'),
                    ];
                    $form['form_simdwdid'] = [
                        '#type' => 'textfield',
                        '#required' =>TRUE,
                        '#title' => t('DWD ID'),
                    ];
                    $form['form_simdwdxcoor'] = [
                        '#type' => 'textfield',
                        '#required' =>TRUE,
                        '#title' => t('DWD Longitude'),
                    ];
                    $form['form_simdwdycoor'] = [
                        '#type' => 'textfield',
                        '#required' =>TRUE,
                        '#title' => t('DWD Latitude'),
                    ];
                }
            }
        }
        //Preview
        if($this->step == 3) 
        {
            $form['dwd'] = [
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
            $form_state->setRebuild();
            $this->step++;
        }
        elseif($this->step == 2) 
        {
            $this->dwdname = $form_state->getValue('form_simdwdname');
            $this->dwdid = $form_state->getValue('form_simdwdid');
            $this->x = $form_state->getValue('form_simdwdxcoor');
            $this->y = $form_state->getValue('form_simdwdycoor');
            $form_state->setRebuild();
            $this->step++;
        }
        elseif($this->step == 3)
        {
            $new_weatherstation_values = array();
            $new_weatherstation_values['type'] = 'sim_weatherstation';
            $new_weatherstation_values['title'] = $this->name;
            $new_weatherstation_values['body'] = $this->description;
           
            $new_weatherstation_values['field_simdwdid'] = $this->dwdid;
            $new_weatherstation_values['field_simdwdname'] = $this->dwdname;
            $new_weatherstation_values['field_simweatherstationid'] = uniqid();
            $new_weatherstation_values['field_simdbtablename'] = 'sim_'.$new_weatherstation_values['field_simweatherstationid'];
            $new_weatherstation_values['field_dwdpoint'] = 'POINT ('.$this->x.' '.$this->y.')';
            $new_weatherstation = entity_create('node', $new_weatherstation_values);
            $new_weatherstation->save();
            db_create_table($new_weatherstation_values['field_simdbtablename'],$this->table);
        }
    }
}