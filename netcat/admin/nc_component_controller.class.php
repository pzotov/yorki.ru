<?php



class nc_component_controller extends nc_ui_controller {

	protected $is_naked = false;

	protected function init() {
		$this->bind('switch_view',   array('cc'));
		$this->bind('set_search_fields', array('cc'));
	}

    protected function after_action($result) {
        // JSON
        if (is_array($result)) {
            return json_safe_encode($result);
        }
        // With template
        if (!$this->is_naked) {
            return BeginHtml() . $result . EndHtml();
        }

        return $result;
    }

	// Переключает режим редактирования данных инфоблока (таблица или выбранный компонент)
	public function action_switch_view($cc) {
		$this->is_naked = false;

		$table = nc_db_table::make('Sub_Class');

		$view_mode = $table->where_id($cc)->get_value('TableViewMode');
		$table->where_id($cc)->update(array('TableViewMode'=>!$view_mode));

		$netcat_url = nc_core()->SUB_FOLDER . nc_core()->HTTP_ROOT_PATH;
		header("Location: {$netcat_url}?inside_admin=1&cc={$cc}");
	}


	public function action_set_search_fields($cc) {
		$fields = nc_core()->input->fetch_post('fields');
		// print_r($fields);
		$sub_class_table = nc_db_table::make('Sub_Class');
		$field_table     = nc_db_table::make('Field');

		$class_id = $sub_class_table->where_id($cc)->get_value('Class_ID');

		$field_table->where('Class_ID', $class_id)->update(array('DoSearch' => 0));

		foreach ($fields as $id) {
			$field_table->where_id($id)->update(array('DoSearch' => 1));
		}

		$back_url = $_SERVER['HTTP_REFERER'];
		if (!$back_url) {
			$back_url = nc_core()->SUB_FOLDER . nc_core()->HTTP_ROOT_PATH . '?inside_admin=1&cc=' . $cc;
		}

		$this->is_naked = true;
		ob_get_level() AND ob_end_clean();
		header("Location: {$back_url}");
		exit;
	}
}