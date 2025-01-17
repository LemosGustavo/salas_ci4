<?php

namespace App\Controllers;

use DateTime;

//defined('BASEPATH') or exit('No direct script access allowed');

class MYController extends BaseController {
    public $sin_rol = FALSE;
    public $edicion = TRUE;

    protected function set_filtro_datos_listar($post_name, $all_string, $column_name, $user_data, &$where_array) {
        if (!empty($_POST[$post_name]) && $this->input->post($post_name) != $all_string) {
            $where['column'] = $column_name;
            $where['value'] = $this->input->post($post_name);
            $where_array[] = $where;
            $this->session->set_userdata($user_data, $this->input->post($post_name));
        } elseif (empty($_POST[$post_name]) && $this->session->userdata($user_data) !== FALSE) {
            $where['column'] = $column_name;
            $where['value'] = $this->session->userdata($user_data);
            $where_array[] = $where;
        } else {
            $this->session->unset_userdata($user_data);
        }
    }

    public function control_combo($opt, $type) {
        $array_name = 'array_' . $type . '_control';
        if (array_key_exists($opt, $this->$array_name)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function get_array($model, $desc = 'descripcion', $id = 'id', $options = array(), $array_registros = array()) {
        if (empty($options)) {
            $options['sort_by'] = $desc;
        }

        $registros = $this->{"{$model}_model"}->get($options);
        if (!empty($registros)) {
            foreach ($registros as $Registro) {
                $array_registros[$Registro->{$id}] = $Registro->{$desc};
            }
        }
        return $array_registros;
    }

    public function set_model_validation_rules($model) {
        foreach ($model->fields as $name => $field) {
            if (empty($field['name'])) {
                $field['name'] = $name;
            }
            if (empty($field['input_type'])) {
                $this->add_input_validation_rules($field);
            } elseif ($field['input_type'] === 'combo') {
                $this->add_combo_validation_rules($field);
            }
        }
    }

    public function add_input_validation_rules($field_opts) {
        $name = $field_opts['name'];
        if (!isset($field_opts['label'])) {
            $label = ucfirst($name);
        } else {
            $label = $field_opts['label'];
        }
        $rules = ''; // xss_clean no se controla mas aca

        if (isset($field_opts['required']) && $field_opts['required']) {
            $rules .= '|required';
        }
        if (isset($field_opts['minlength'])) {
            $rules .= '|min_length[' . $field_opts['minlength'] . ']';
        }
        if (isset($field_opts['maxlength'])) {
            $rules .= '|max_length[' . $field_opts['maxlength'] . ']';
        }
        if (isset($field_opts['maxvalue'])) {
            $rules .= '|less_than_equal_to[' . $field_opts['maxvalue'] . ']';
        }
        if (isset($field_opts['max'])) {
            $rules .= '|less_than_equal_to[' . $field_opts['max'] . ']';
        }
        if (isset($field_opts['min'])) {
            $rules .= '|greater_than_equal_to[' . $field_opts['min'] . ']';
        }
        if (isset($field_opts['matches'])) {
            $rules .= '|matches[' . $field_opts['matches'] . ']';
        }

        if (isset($field_opts['type'])) {
            switch ($field_opts['type']) {
                case 'integer':
                    $rules .= '|integer';
                    break;
                case 'numeric':
                    $rules .= '|numeric';
                    break;
                case 'decimal':
                    $rules .= '|decimal';
                    break;
                case 'money':
                    $rules .= '|money';
                    break;
                case 'date':
                    $rules .= '|validate_date';
                    break;
                case 'time':
                    $rules .= '|validate_time';
                    break;
                case 'datetime':
                    $rules .= '|validate_datetime';
                    break;
                case 'cbu':
                    $rules .= '|validate_cbu';
                    break;
                case 'email':
                    $rules .= '|valid_email';
                    break;
                default:
                    break;
            }
        }
        if (empty($rules)) {
            $rules = 'trim';
        }

        $this->form_validation->set_rules($name, $label, trim($rules, '|'));
    }

    public function add_combo_validation_rules($field_opts) {
        $validation = \Config\Services::validation();
        $name = $field_opts['name'];
        if (!isset($field_opts['arr_name'])) {
            $arr_name = $field_opts['name'];
        } else {
            $arr_name = $field_opts['arr_name'];
        }

        if (!isset($field_opts['label'])) {
            $label = ucfirst($name);
        } else {
            $label = $field_opts['label'];
        }

        $rules = "callback_control_combo[$arr_name]";
        if (isset($field_opts['type']) && $field_opts['type'] === 'multiple') {
            $validation->setRule($name . '[]', $label, $rules);
        } else {
            $validation->setRules($name, $label, $rules);
        }
    }

    public function add_input_field(&$field_array, $field_opts, $def_value = NULL) {
        if ($def_value === NULL) {
            $field['value'] = set_value($field_opts['name']);
        } else {
            $field['value'] = set_value($field_opts['name'], $def_value);
        }

        foreach ($field_opts as $key => $field_opt) {
            $field[$key] = $field_opt;
        }
        $field['id'] = $field_opts['name'];
        $field['class'] = 'form-control' . (empty($field_opts['class']) ? '' : " {$field_opts['class']}");
        if (isset($field_opts['type'])) {
            switch ($field_opts['type']) {
                case 'cbu':
                    $field['pattern'] = '[0-9]*';
                    $field['title'] = 'Debe ingresar sólo números';
                    $field['type'] = 'text';
                    break;
                case 'email':
                    $field['pattern'] = "(?!(^[.-].*|[^@]*[.-]@|.*\.{2,}.*)|^.{254}.)([a-zA-Z0-9!#$%&'*+\/=?^_`{|}~.-]+@)(?!-.*|.*-\.)([a-zA-Z0-9-]{1,63}\.)+[a-zA-Z]{2,15}";
                    $field['title'] = 'Debe ingresar un email válido';
                    $field['type'] = 'text';
                    break;
                case 'integer':
                    $field['pattern'] = '^(0|[1-9][0-9]*)$';
                    $field['title'] = 'Debe ingresar sólo números';
                    $field['type'] = 'text';
                    break;
                case 'numeric':
                    $field['pattern'] = '[0-9]*[.,]?[0-9]+';
                    $field['title'] = 'Debe ingresar sólo números decimales';
                    $field['type'] = 'text';
                    break;
                case 'money':
                    $field['pattern'] = '[-]?(\d{1,3}(\.\d{3})*|\d+)(,\d{1,2})?';
                    $field['title'] = 'Debe ingresar un importe';
                    $field['type'] = 'text';
                    if ($def_value !== NULL) {
                        $field['value'] = set_value($field_opts['name'], str_replace('.', ',', $def_value));
                    }
                    break;
                case 'date':
                    if (empty($field_opts['class'])) {
                        $field['class'] .= ' dateFormat';
                    }
                    $field['type'] = 'text';
                    if ($def_value !== NULL) {
                        $field['value'] = set_value($field_opts['name'], empty($def_value) ? '' : date_format(new DateTime($def_value), 'd/m/Y'));
                    }
                    break;
                case 'time':
                    $field['pattern'] = '(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]';
                    $field['title'] = 'Debe ingresar una hora válida';
                    $field['type'] = 'text';
                    break;
                case 'datetime':
                    if (empty($field_opts['class'])) {
                        $field['class'] .= ' dateTimeFormat';
                    }
                    $field['type'] = 'text';
                    if ($def_value !== NULL) {
                        $field['value'] = set_value($field_opts['name'], empty($def_value) ? '' : date_format(new DateTime($def_value), 'd/m/Y H:i'));
                    }
                    break;
                case 'password':
                    $field['type'] = 'password';
                    break;
                default:
                    break;
            }
        }

        $field['label'] = form_label($field_opts['label'], $field_opts['name']);
        $field_array[$field_opts['name']] = $field;
        $form_type = empty($field['form_type']) ? 'input' : $field['form_type'];
        unset($field['disabled']);
        unset($field['form_type']);
        unset($field['label']);
        unset($field['required']);
        unset($field['minlength']);
        unset($field['matches']);

        if (!empty($field_opts['disabled']) && $field_opts['disabled']) {
            $field['disabled'] = '';
        }

        if (!empty($field_opts['required']) && $field_opts['required']) {
            $field['required'] = '';
        }

        if (!empty($field_opts['error_text'])) {
            $field['data-error'] = $field_opts['error_text'];
        }

        if (!empty($field_opts['minlength'])) {
            $field['data-minlength'] = $field_opts['minlength'];
        }

        if (!empty($field_opts['val_match'])) {
            if (!empty($field_opts['val_match_text'])) {
                $field['data-match-error'] = $field_opts['val_match_text'];
            }
            $field['data-match'] = '#' . $field_opts['val_match'];
        }

        if ($form_type === 'input') {
            $form = form_input($field);
        } elseif ($form_type === 'textarea') {
            $form = form_textarea($field);
        }

        //		if (!empty($field_opts['required']) && $field_opts['required']) {
        //			if (isset($field_opts['type']) && $field_opts['type'] === 'money') {
        //				$form = '<div class="input-group"><span class="input-group-addon"><i class="fa fa-dollar"></i></span>' . $form . '<span title="Requerido" class="input-group-addon"><i class="fa fa-exclamation"></i></span></div>';
        //			} else {
        //				$form = '<div class="input-group">' . $form . '<span title="Requerido" class="input-group-addon"><i class="fa fa-exclamation"></i></span></div>';
        //			}
        //		} else {
        if (isset($field_opts['type']) && $field_opts['type'] === 'money') {
            $form = '<div class="input-group"><span class="input-group-addon"><i class="fa fa-dollar"></i></span>' . $form . '</div>';
        }
        if (isset($field_opts['type']) && $field_opts['type'] === 'caracteristica') {
            $form = '<div class="input-group">' . $form . '<span class="input-group-btn"><a class="btn btn-warning" href="' . $field_opts['url'] . '" data-remote="false" data-toggle="modal" data-target="#remote_modal"><i class="fa fa-edit"></i></a></span></div>';
        }
        //		}
        $field_array[$field_opts['name']]['form'] = $form;
    }

    public function add_combo_field(&$field_array, $field_opts, $def_value = NULL) {
        $values = $field_opts['array'];
        if ($def_value == NULL) {
            $field['value'] = $this->form_validation->set_value($field_opts['name'], isset($field_opts['value']) ? $field_opts['value'] : NULL);
        } else {
            $field['value'] = $this->form_validation->set_value($field_opts['name'], $def_value);
        }

        $field_array[$field_opts['name']]['required'] = empty($field_opts['required']) ? FALSE : $field_opts['required'];
        if (!isset($field_opts['label'])) {
            $field_opts['label'] = ucfirst($field_opts['name']);
        }

        unset($field['disabled']);

        $label = form_label($field_opts['label'], $field_opts['name']);

        $extras = '';
        if (!empty($field_opts['disabled']) && $field_opts['disabled']) {
            $extras .= ' disabled';
        }

        if (!empty($field_opts['required']) && $field_opts['required']) {
            $extras .= ' required';
        }

        if (!empty($field_opts['error_text'])) {
            $extras .= ' data-error="' . $field_opts['error_text'] . '"';
        }

        if (empty($field_opts['class'])) {
            $extraClass = ' selectize';
        } else {
            $extraClass = ' ' . $field_opts['class'];
        }

        if (!empty($field_opts['tabindex'])) {
            $extras .= ' tabindex=' . $field_opts['tabindex'];
        }

        if (isset($field_opts['type']) && $field_opts['type'] === 'multiple') {
            $script = '';
            $form = form_dropdown($field_opts['name'] . '[]', $values, $field['value'], 'class="form-control' . $extraClass . '" id="' . $field_opts['name'] . '" multiple tabindex="-1" aria-hidden="true"' . $extras);
        } else {
            $script = '';
            $form = form_dropdown($field_opts['name'], $values, $field['value'], 'class="form-control' . $extraClass . '" id="' . $field_opts['name'] . '"' . $extras);
        }

        $field_array[$field_opts['name']]['label'] = $script . $label;
        $field_array[$field_opts['name']]['form'] = $form;
    }

    protected function build_fields($model_fields, $registro = NULL, $readonly = FALSE) {
        $fields = array();
        foreach ($model_fields as $name => $field) {
            if ($readonly) {
                $field['disabled'] = TRUE;
                unset($field['array']);
                unset($field['input_type']);
                unset($field['required']);
            }
            $field['name'] = $name;
            if (empty($field['input_type'])) {
                $this->add_input_field($fields, $field, isset($registro) ? $registro->{$name} : NULL);
            } elseif ($field['input_type'] == 'combo') {
                if (isset($field['id_name'])) {
                    $this->add_combo_field($fields, $field, isset($registro) ? $registro->{$field['id_name']} : NULL);
                } else {
                    $this->add_combo_field($fields, $field, isset($registro) ? $registro->{"{$name}_id"} : NULL);
                }
            }
        }
        return $fields;
    }

    protected function get_date_sql($post = 'fecha', $src_format = 'd/m/Y', $dst_format = 'Y-m-d') {
        $fecha = DateTime::createFromFormat($src_format, $post);
        if (!empty($fecha)) {
            $fecha_sql = date_format($fecha, $dst_format);
        } else {
            return 'NULL';
        }
        return $fecha_sql;
    }

    protected function get_datetime_sql($post = 'fecha', $src_format = 'd/m/Y H:i:s', $dst_format = 'Y-m-d H:i:s') {
        return $this->get_date_sql($post, $src_format, $dst_format);
    }

    protected function modal_error($error_msg = '', $error_title = 'Error general') {
        $data['error_msg'] = $error_msg;
        $data['error_title'] = $error_title;
        $this->load->view('errors/html/error_modal', $data);
    }

    protected function readonly($field) {
        unset($field['id_name']);
        unset($field['array']);
        unset($field['input_type']);
        unset($field['required']);
        $field['readonly'] = TRUE;
        $field['disabled'] = TRUE;
        return $field;
    }
}
/* End of file MY_Controller.php */
