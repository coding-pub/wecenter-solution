<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2014 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|
+---------------------------------------------------------------------------
*/


if (!defined('IN_ANWSION'))
{
	die;
}

class solution extends AWS_ADMIN_CONTROLLER
{
	public function setup()
	{
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(310));
	}

	public function list_action()
	{
		$this->crumb(AWS_APP::lang()->_t('参考答案'), 'admin/solution/list/');

		if ($_POST)
		{
			foreach ($_POST as $key => $val)
			{
				if ($key == 'start_date' OR $key == 'end_date')
				{
					$val = base64_encode($val);
				}

				if ($key == 'keyword' OR $key == 'user_name')
				{
					$val = rawurlencode($val);
				}

				$param[] = $key . '-' . $val;
			}

			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => get_js_url('/admin/solution/list/' . implode('__', $param))
			), 1, null));
		}

		$where = array();

		if ($_GET['keyword'])
		{
			$where[] = "solution_content LIKE '" . $this->model('solution')->quote($_GET['keyword']) . "%'";
		}

		if ($_GET['user_name'])
		{
			$user_info = $this->model('account')->get_user_info_by_username($_GET['user_name']);

			$where[] = 'published_uid = ' . intval($user_info['uid']);
		}

		if ($_GET['solution_status'] OR $_GET['solution_status'] == '0')
		{
			$where[] = 'status = ' . intval($_GET['solution_status']);
		}

		if (base64_decode($_GET['start_date']))
		{
			$where[] = 'update_time >= ' . strtotime(base64_decode($_GET['start_date']));
		}

		if (base64_decode($_GET['end_date']))
		{
			$where[] = 'update_time <= ' . strtotime('+1 day', strtotime(base64_decode($_GET['end_date'])));
		}

		$solution_list = $this->model('solution')->get_solution_list(implode(' AND ', $where), 'solution_id DESC', $this->per_page, $_GET['page']);

		$total_rows = $this->model('solution')->found_rows();

		if ($solution_list)
		{
			foreach ($solution_list AS $key => $solution_info)
			{
				$question_ids[] = $solution_list[$key]['question_id'];
				$published_uids[] = $solution_list[$key]['published_uid'];
			}

			$users_info_query = $this->model('account')->get_user_info_by_uids($published_uids);

			if ($users_info_query)
			{
				foreach ($users_info_query AS $user_info)
				{
					$users_info[$user_info['uid']] = $user_info;
				}
			}

			$questions_info = $this->model('question')->get_question_info_by_ids($question_ids);
		}

		$url_param = array();

		foreach($_GET as $key => $val)
		{
			if (!in_array($key, array('app', 'c', 'act', 'page')))
			{
				$url_param[] = $key . '-' . $val;
			}
		}

		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/admin/solution/list/') . implode('__', $url_param),
			'total_rows' => $total_rows,
			'per_page' => $this->per_page
		))->create_links());

		TPL::assign('solutions_count', $total_rows);
		TPL::assign('list', $solution_list);
		TPL::assign('users_info', $users_info);
		TPL::assign('questions_info', $questions_info);
		
		TPL::output('admin/solution/list');
	}
}