<?php

namespace App\Adminux;

class Form
{
    protected $_model;
    protected $_fields = [];

    public $_label_cls = 'col-sm-2 col-form-label text-muted';
    public $_input_cls = 'col-sm-10';
    public $_checkbox_value = 'Y';
    public $_checked_if = [1, 'on', 'true', 'y', 'yes'];

    public function __construct($model = '')
    {
        $this->_model = $model;
    }

    public function date($params)
    {
        $value = $this->getValue($params);
        if(!empty($value)) $value = date('Y-m-d', strtotime($this->getValue($params)));
        $params['input'] = '<input type="date" class="form-control" id="'.$this->getId($params).'" name="'.$this->getName($params).'" value="'.$value.'" '.$this->getEditable($params).'>';
        return $this->getFormGroup($params);
    }

    public function time($params)
    {
        $value = $this->getValue($params);
        if(!empty($value)) $value = date('H:i:s', strtotime($this->getValue($params)));
        $params['input'] = '<input type="time" class="form-control" id="'.$this->getId($params).'" name="'.$this->getName($params).'" value="'.$value.'" '.$this->getEditable($params).'>';
        return $this->getFormGroup($params);
    }

    public function datetime($params)
    {
        $value = $this->getValue($params);
        if(!empty($value)) $value = date('Y-m-d\TH:i:s', strtotime($this->getValue($params)));
        $params['input'] = '<input type="datetime-local" class="form-control" id="'.$this->getId($params).'" name="'.$this->getName($params).'" value="'.$value.'" '.$this->getEditable($params).'>';
        return $this->getFormGroup($params);
    }

    public function display($params)
    {
        $params['input'] = '<input type="text" readonly class="form-control-plaintext" id="'.$this->getId($params).'" name="'.$this->getName($params).'" value="'.$this->getValue($params).'">';
        return $this->getFormGroup($params);
    }

    public function email($params)
    {
        $params['input'] = '<input type="email" class="form-control" id="'.$this->getId($params).'" name="'.$this->getName($params).'" value="'.$this->getValue($params).'" '.$this->getEditable($params).'>';
        return $this->getFormGroup($params);
    }

    public function enum($params)
    {
        $sql_result = \DB::select(\DB::raw('SHOW COLUMNS FROM '.$this->_model->getTable().' WHERE Field = "'.$this->getName($params).'"'))[0]->Type;
        preg_match_all("/'([^']+)'/", $sql_result, $matches);

        if(!empty($matches[1])) {
            foreach($matches[1] as $string) {
                $sel = ($string == $this->getValue($params)) ? ' selected' : '';
                $options[] = '<option value="'.$string.'"'.$sel.'>'.$string.'</option>';
            }
        }

        $params['options'] = $options;
        return $this->select($params);
    }

    public function file($params)
    {
        $tooltip = 'data-toggle="tooltip" data-placement="top" title="Max File: '.ini_get('upload_max_filesize').', Max Form: '.ini_get('post_max_size').'"';
        $types = !empty($params['accept'] && is_array($params['accept'])) ? 'accept="'.implode(',', $params['accept']).'"' : '';

        $params['input'] = '<div class="input-group">
                                <input type="file" class="custom-file-input" '.$types.' id="'.$this->getId($params).'" name="'.$this->getName($params).'" value="'.$this->getValue($params).'" '.$tooltip.'>
                                <label class="custom-file-label" for="'.$this->getId($params).'">Choose file</label>
                            </div>';
        return $this->getFormGroup($params);
    }

    public function moduleConfig($params)
    {
        $path = (!empty($params['path'])) ? $params['path'] : class_basename($this->_model);

        $config = \App\Adminux\Helper::getConfig($path);
        if(!empty($config)) {
            $values = @json_decode($this->getValue($params), true);
            if($values === null) $values = @json_decode($this->_model->getAttributes()[$this->getName($params)], true);

            foreach($config as $key => $desc) {
                $params['input'][] = '<tr>
                                        <td>'.$key.'</td>
                                        <td class="w-50"><input type="text" class="form-control" id="'.$this->getId($params).'" name="'.$this->getName($params).'['.$key.']" value="'.@$values[$key].'"></td>
                                        <td class="pl-3"><small>'.$desc.'</small></td>
                                    </tr>';
            }

            $params['input'] = '<table class="w-100">'.implode('', $params['input']).'</table>';
            return $this->getFormGroup($params);
        }
    }

    public function number($params)
    {
        $params['input'] = '<input type="number" class="form-control" id="'.$this->getId($params).'" name="'.$this->getName($params).'" value="'.$this->getValue($params).'" '.$this->getEditable($params).'>';
        return $this->getFormGroup($params);
    }

    public function password($params)
    {
        $params['input'] = '<input type="password" class="form-control" id="'.$this->getId($params).'" name="'.$this->getName($params).'">';
        return $this->getFormGroup($params);
    }

    public function select($params)
    {
        if(empty($params['options'])) {
            foreach($this->_model->{strtolower($params['label'])}()->getRelated()->all() as $val) { // withTrashed()->get()

                if(isset($params['allows']) && !in_array($val->id, $params['allows'])) continue;

                $sel = ($val->id == $this->getValue($params)) ? ' selected' : '';
                $name = (!empty($val->name)) ? $val->name : $val->{strtolower($params['label'])};
                $options[] = '<option value="'.$val->id.'"'.$sel.'>'.$val->id.' - '.$name.'</option>';
            }
        } else $options = $params['options'];

        $params['input'] = '<select class="form-control" id="'.$this->getId($params).'" name="'.$this->getName($params).'" '.$this->getEditable($params).'>
                            <option value="">'.__('adminux.select').'...</option>
                            '.@implode('', $options).'
                            </select>';
        return $this->getFormGroup($params);
    }

    public function selectProduct($params)
    {
        $values = (new \App\Adminux\Account\Models\AccountProduct)
        ->select('accounts_products.id','accounts_products.account_id','accounts.email','services_plans.plan','services.service','partners.partner')
        ->join('services_plans', 'services_plans.id', '=', 'accounts_products.plan_id')
        ->join('services', 'services.id', '=', 'services_plans.service_id')
        ->join('software', 'software.id', '=', 'services.software_id')
        ->join('partners', 'partners.id', '=', 'services.partner_id')
        ->join('accounts', 'accounts.id', '=', 'accounts_products.account_id')
        ->whereIn('services_plans.service_id', Helper::getSelectedServices())
        ->whereIn('software.software', $params['software'])
        ->orderBy('partners.partner', 'asc')
        ->orderBy('accounts_products.account_id', 'asc')
        ->get();

        foreach($values as $product) {
            $sel = ($product->id == $this->getValue($params)) ? ' selected' : '';
            $params['options'][] = '<option value="'.$product->id.'"'.$sel.'>'.$product->partner.' > '.$product->account_id.' - '.$product->email.' > '.$product->id.' - '.$product->plan.'</option>';
        }

        return $this->select($params);
    }

    public function switch($params)
    {
        $value   = (!empty($params['value'])) ? $params['value'] : $this->_checkbox_value;
        $checked = (in_array(strtolower($this->getValue($params)), $this->_checked_if)) ? ' checked' : '';

        $this->_input_cls.= ' form-check form-switch ps-5';
        $params['input'] = '<input type="hidden" name="'.$this->getName($params).'">
                            <input type="checkbox" class="form-check-input mt-2" id="'.$this->getId($params).'" name="'.$this->getName($params).'" value="'.$value.'"'.$checked.'>
                            <label class="form-check-label" for="'.$this->getId($params).'"></label>';
        return $this->getFormGroup($params);
    }

    public function text($params)
    {
        $params['input'] = '<input type="text" class="form-control" id="'.$this->getId($params).'" name="'.$this->getName($params).'" value="'.$this->getValue($params).'" '.$this->getEditable($params).'>';
        return $this->getFormGroup($params);
    }

    public function textarea($params)
    {
        $params['input'] = '<textarea class="form-control" id="'.$this->getId($params).'" name="'.$this->getName($params).'" '.$this->getEditable($params).'>'.$this->getValue($params).'</textarea>';
        return $this->getFormGroup($params);
    }

    public function url($params)
    {
        $params['input'] = '<input type="url" class="form-control" id="'.$this->getId($params).'" name="'.$this->getName($params).'" value="'.$this->getValue($params).'" '.$this->getEditable($params).'>';
        return $this->getFormGroup($params);
    }

    public function getFormGroup($params = [])
    {
        return '<div class="row mb-3">
                    <label class="'.$this->_label_cls.'" for="'.$this->getId($params).'">'.$params['label'].'</label>
                    <div class="'.$this->_input_cls.'">'.$params['input'].'</div>
                </div>';
    }

    public function getEditable($params = [])
    {
        return ($this->_model->id && isset($params['editable']) && $params['editable'] === false) ? ' disabled' : '';
    }

    public function getId($params = [])
    {
        if(!empty($params['id'])) return $params['id'];
        elseif(!empty($params['name'])) return $params['name'];
        else return $this->getName($params);
    }

    public function getName($params = [])
    {
        if(!empty($params['name'])) return $params['name'];

        if(!empty($params['label'])) {
            if(strcasecmp($params['label'], 'id') == 0) return 'id';
            foreach(\Schema::getColumnListing($this->_model->getTable()) as $key) {
                if(strcasecmp($key, $params['label']) == 0) return $key;
                elseif(strcasecmp($key, str_replace(['-', ' '], '', $params['label'])) == 0) return $key;
                elseif(strcasecmp($key, str_replace(['-', ' '], '_', $params['label'])) == 0) return $key;
                elseif(strcasecmp($key, $params['label'].'_id') == 0) return $key;
                elseif(strcasecmp($key, $params['label'].'_at') == 0) return $key;
            }
        }

        return '';
    }

    public function getValue($params = [])
    {
        if(!empty($params['value'])) return $params['value'];

        return old($this->getName($params), @$this->_model->getAttributes()[$this->getName($params)]);
    }

    public function addFields($fields = [])
    {
        $this->_fields = array_merge($this->_fields, $fields);
    }

    public function getFields()
    {
        return $this->_fields;
    }

    public function html($html = '')
    {
        return $html;
    }
}
