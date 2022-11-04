<?php

namespace App\Integrations;

use App\Constants\CategoryAttributeLevel;
use App\Constants\CategoryAttributeType;

class TransformedAttribute
{

    public $integrationId;
    public $name;
    public $label;
    public $type;
    public $required;
    public $level;
    public $data;
    public $additionalData;

    /**
     * TransformedAddress constructor.
     *
     * @param $integrationId
     * @param $name
     * @param $label
     * @param $type
     * @param $required
     * @param $level
     * @param $data
     * @param $additionalData
     */
    public function __construct(
        $integrationId,
        $name, 
        $label, 
        $type, 
        $required,
        $level = 0,
        $data = null, 
        $additionalData = null)
    {
        $this->integrationId = $integrationId;
        $this->name = $name;
        $this->label = $label;
        $this->data = $data;
        $this->type = $type;
        $this->required = $required;
        $this->level = $level;
        $this->additionalData = $additionalData;

        if (empty($this->name)) {
            set_log_extra('data', get_object_vars($this));
            throw new \Exception('Attribute name is empty.');
        }

        if (empty($this->label)) {
            set_log_extra('data', get_object_vars($this));
            throw new \Exception('Attribute label is empty.');
        }

        if (!is_bool($this->required)) {
            set_log_extra('data', get_object_vars($this));
            throw new \Exception('Attribute required is not boolean');
        }

        if (!CategoryAttributeType::isValid($this->type)) {
            set_log_extra('data', get_object_vars($this));
            throw new \Exception('Attribute type not recognized.');
        }

        if (!CategoryAttributeLevel::isValid($this->level)) {
            set_log_extra('data', get_object_vars($this));
            throw new \Exception('Attribute level not recognized.');
        }
    }

    /**
     * Create categories attribute 
     *
     * @return array
     */
    public function createAndFormatAttribute()
    {
        return [
            'integration_id' => $this->integrationId,
            'name' => $this->name,
            'label' => $this->label,
            'data' => $this->data,
            'type' => $this->type,
            'required' => $this->required,
            'level' => $this->level,
            'additional_data' => $this->additionalData
        ];
    }

}
