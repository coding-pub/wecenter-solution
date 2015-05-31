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

class main extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white'; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
		$rule_action['actions'] = array();
		return $rule_action;
	}

	public function setup()
	{
		$this->crumb(AWS_APP::lang()->_t('参考答案'), '/solution/');
	}

	public function index_action()
	{
		if ($_GET['id'])
		{
			if (!$solution_info = $this->model('solution')->get_solution_info_by_question_id($_GET['id']))
			{
				H::redirect_msg(AWS_APP::lang()->_t('指定问题不存在'));
			}

			if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'] AND !$this->user_info['permission']['edit_question'] AND $solution_info['question_uid'] != $this->user_id)
			{
				H::redirect_msg(AWS_APP::lang()->_t('你没有权限编辑这个参考答案'), '/question/' . $solution_info['question_id']);
			}
		}
		else if (!$this->user_info['permission']['publish_question'])
		{
			H::redirect_msg(AWS_APP::lang()->_t('你所在用户组没有权限发布参考答案'));
		}
		else if ($this->is_post() AND $_POST['solution_content'])
		{
			$solution_info = array(
				'solution_content' => htmlspecialchars($_POST['solution_content']),
				'view_cost' => intval($_POST['view_cost'])
			);
		}

		if ($this->user_info['integral'] < 0 AND get_setting('integral_system_enabled') == 'Y' AND !$_GET['id'])
		{
			H::redirect_msg(AWS_APP::lang()->_t('你的剩余积分已经不足以进行此操作'));
		}

		if (($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator'] OR $solution_info['question_uid'] == $this->user_id AND $_GET['id']) OR !$_GET['id'])
		{
			TPL::assign('attach_access_key', md5($this->user_id . time()));
		}

		TPL::assign('human_valid', human_valid('question_valid_hour'));

		TPL::import_js('js/app/publish.js');

		if (get_setting('advanced_editor_enable') == 'Y')
		{
			import_editor_static_files();
		}

		if (get_setting('upload_enable') == 'Y')
		{
			// fileupload
			TPL::import_js('js/fileupload.js');
		}

		TPL::assign('solution_info', $solution_info);
		TPL::output('solution/index');
	}

	public function view_action()
	{
		if (is_mobile())
		{	
			if($_GET['ask'])
			{
				HTTP::redirect('/m/view_solution/' . $_GET['id'] . '?ask=1');
			}
			else
			{
				HTTP::redirect('/m/view_solution/' . $_GET['id']);
			}
		}

		if ($_GET['id'])
		{
			if (!$solution_info = $this->model('solution')->get_solution_info_by_question_id($_GET['id']))
			{
				H::redirect_msg(AWS_APP::lang()->_t('指定问题不存在'));
			}
			
			if($this->user_info['permission']['is_administortar'] || $this->user_info['permission']['is_moderator'] || $this->user_id == $solution_info['published_uid'])
			{
				$user_solution['user_id'] = $this->user_id;
				$user_solution['solution_id'] = $solution_info['solution_id'];
				$user_solution['enough_integral'] = True;
			}
			else if($user_solution = $this->model('solution')->get_user_solution_record($this->user_id, $solution_info['solution_id']))
			{
				$user_solution['enough_integral'] = True;
			}
			else
			{
				if($this->user_info['integral'] < $solution_info['view_cost'])
				{
					$user_solution['enough_integral'] = False;
				}
				else
				{
					// 扣除当前用户的积分

					$this->model('integral')->process($this->user_id, 'VIEW_SOLUTION', -$solution_info['view_cost'], '查看问题 #' . $solution_info['question_id'] . '的参考答案', $solution_info['question_id']);

					// 增加查看记录

					$this->model('solution')->save_user_solution_record($this->user_id, $solution_info['solution_id']);

					// 增加撰写参考答案用户的积分

					$this->model('integral')->process($solution_info['published_uid'], 'SOLUTION_PROFIT', $solution_info['view_cost'], '您的关于问题 #' . $solution_info['question_id'] . '的参考答案被用户 #' . $this->user_id . '查看', $solution_info['question_id']);

					$user_solution['user_id'] = $this->user_id;
					$user_solution['solution_id'] = $this->solution_info['solution_id'];
					$user_solution['enough_integral'] = True;
				}
			}

			TPL::assign('user_solution', $user_solution);
			TPL::assign('solution_info', $solution_info);
			TPL::output('solution/view');
		}
		else
		{
			$url = '/';
			H::redirect_msg(AWS_APP::lang()->_t('找不到指定的参考答案...'), $url);
		}

	}

	public function wait_approval_action()
	{
		if ($_GET['question_id'])
		{
			if ($_GET['_is_mobile'])
			{
				$url = '/m/question/' . $_GET['question_id'];
			}
			else
			{
				$url = '/question/' . $_GET['question_id'];
			}
		}
		else
		{
			$url = '/';
		}

		H::redirect_msg(AWS_APP::lang()->_t('提交成功, 请等待管理员审核...'), $url);
	}
}
