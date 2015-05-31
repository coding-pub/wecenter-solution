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

define('IN_AJAX', TRUE);


if (!defined('IN_ANWSION'))
{
    die;
}

class ajax extends AWS_CONTROLLER
{
    public function get_access_rule()
    {
        $rule_action['rule_type'] = 'white'; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
        $rule_action['actions'] = array();

        return $rule_action;
    }

    public function setup()
    {
        HTTP::no_cache_header();
    }

    public function modify_solution_action()
    {
        if (!$solution_info = $this->model('solution')->get_solution_info_by_question_id($_POST['question_id']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题不存在')));
        }

        if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'] AND !$this->user_info['permission']['edit_question'])
        {
            if ($solution_info['published_uid'] != $this->user_id)
            {
                H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限编辑这个参考答案')));
            }
        }

        if (!$this->user_info['permission']['publish_url'] AND FORMAT::outside_url_exists($_POST['solution_content']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你所在的用户组不允许发布站外链接')));
        }

        //if (!$this->model('publish')->insert_attach_is_self_upload($_POST['question_detail'], $_POST['attach_ids']))
        //{
        //    H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('只允许插入当前页面上传的附件')));
        //}

        if (human_valid('question_valid_hour') AND !AWS_APP::captcha()->is_validate($_POST['seccode_verify']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请填写正确的验证码')));
        }

        // !注: 来路检测后面不能再放报错提示
        if (!valid_post_hash($_POST['post_hash']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('页面停留时间过长,或内容已提交,请刷新页面')));
        }

        if ($_POST['do_delete'] AND !$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对不起, 你没有删除问题的权限')));
        }

        if ($_POST['do_delete'])
        {
            if ($this->user_id != $solution_info['published_uid'])
            {
                $this->model('account')->send_delete_message($solution_info['published_uid'], $solution_info['question_content'], $solution_info['solution_content']);
            }

            $this->model('solution')->remove_solution_by_ids($solution_info['solution_id']);

            H::ajax_json_output(AWS_APP::RSM(array(
                'url' => get_js_url('/question/' . $solution_info['question_id'])
            ), 1, null));
        }

        $this->model('solution')->update_solution($solution_info['solution_id'], $_POST['solution_content'], $_POST['solution_view_cost'], $this->user_id);

        if ($_POST['_is_mobile'])
        {
            if ($weixin_user = $this->model('openid_weixin_weixin')->get_user_info_by_uid($this->user_id))
            {
                if ($weixin_user['location_update'] > time() - 7200)
                {
                    $this->model('geo')->set_location('solution', $question_id, $weixin_user['longitude'], $weixin_user['latitude']);
                }
            }

            $url = get_js_url('/solution/wait_approval/question_id-' . $solution_info['question_id']);
        }
        else
        {
            $url = get_js_url('/solution/wait_approval/question_id-' . $solution_info['question_id']);
        }
        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => $url
        ), 1, null));
    }

    public function new_solution_action()
    {
        if (!$this->user_info['permission']['publish_question'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限发布答案')));
        }

        if ($this->user_info['integral'] < 0 AND get_setting('integral_system_enabled') == 'Y')
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余积分已经不足以进行此操作')));
        }

        if (!$_POST['solution_content'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请输入答案内容')));
        }

        if (!$_POST['solution_view_cost'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入查看答案需要消耗的积分')));
        }

        if (!$this->user_info['permission']['publish_url'] AND FORMAT::outside_url_exists($_POST['solution_content']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你所在的用户组不允许发布站外链接')));
        }

        if (human_valid('question_valid_hour') AND !AWS_APP::captcha()->is_validate($_POST['seccode_verify']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请填写正确的验证码')));
        }

        //if (!$this->model('publish')->insert_attach_is_self_upload($_POST['question_detail'], $_POST['attach_ids']))
        //{
        //    H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('只允许插入当前页面上传的附件')));
        //}

        if ($_POST['weixin_media_id'])
        {
            $_POST['weixin_media_id'] = base64_decode($_POST['weixin_media_id']);

            $weixin_pic_url = AWS_APP::cache()->get('weixin_pic_url_' . md5($_POST['weixin_media_id']));

            if (!$weixin_pic_url)
            {
                H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('图片已过期或 media_id 无效')));
            }

            $file = $this->model('openid_weixin_weixin')->get_file($_POST['weixin_media_id']);

            if (!$file)
            {
                H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('远程服务器忙')));
            }

            if (is_array($file) AND $file['errmsg'])
            {
                H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('获取图片失败，错误为: %s', $file['errmsg'])));
            }

            AWS_APP::upload()->initialize(array(
                'allowed_types' => get_setting('allowed_upload_types'),
                'upload_path' => get_setting('upload_dir') . '/questions/' . gmdate('Ymd'),
                'is_image' => TRUE,
                'max_size' => get_setting('upload_size_limit')
            ));

            AWS_APP::upload()->do_upload($_POST['weixin_media_id'] . '.jpg', $file);

            $upload_error = AWS_APP::upload()->get_error();

            if ($upload_error)
            {
                switch ($upload_error)
                {
                    default:
                        H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('保存图片失败，错误为 %s' . $upload_error)));

                        break;

                    case 'upload_invalid_filetype':
                        H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('保存图片失败，本站不允许上传 jpeg 格式的图片')));

                        break;

                    case 'upload_invalid_filesize':
                        H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('图片尺寸过大, 最大允许尺寸为 %s KB', get_setting('upload_size_limit'))));

                        break;
                }
            }

            $upload_data = AWS_APP::upload()->data();

            if (!$upload_data)
            {
                H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('保存图片失败，请与管理员联系')));
            }

            foreach (AWS_APP::config()->get('image')->attachment_thumbnail AS $key => $val)
            {
                $thumb_file[$key] = $upload_data['file_path'] . $val['w'] . 'x' . $val['h'] . '_' . basename($upload_data['full_path']);

                AWS_APP::image()->initialize(array(
                    'quality' => 90,
                    'source_image' => $upload_data['full_path'],
                    'new_image' => $thumb_file[$key],
                    'width' => $val['w'],
                    'height' => $val['h']
                ))->resize();
            }

            $this->model('solution')->add_attach('solution', $upload_data['orig_name'], $_POST['attach_access_key'], time(), basename($upload_data['full_path']), true);
        }

        // !注: 来路检测后面不能再放报错提示
        //if (!valid_post_hash($_POST['post_hash']))
        //{
        //    H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('页面停留时间过长,或内容已提交,请刷新页面')));
        //}

        $solution_id = $this->model('solution')->save_solution($_POST['question_id'], $_POST['solution_content'], $_POST['solution_view_cost'], $this->user_id);
        if ($_POST['_is_mobile'])
        {
            if ($weixin_user = $this->model('openid_weixin_weixin')->get_user_info_by_uid($this->user_id))
            {
                if ($weixin_user['location_update'] > time() - 7200)
                {
                    $this->model('geo')->set_location('solution', $question_id, $weixin_user['longitude'], $weixin_user['latitude']);
                }
            }

            $url = get_js_url('/solution/wait_approval/question_id-' . $_POST['question_id']);
        }
        else
        {
            $url = get_js_url('/solution/wait_approval/question_id-' . $_POST['question_id']);
        }

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => $url
        ), 1, null));
    }

    public function view_solution_action()
    {
        if(!$this->user_id)
        {
            H::ajax_json_output(AWS_APP::RSM(array(
              'url' => get_js_url('/account/register/')
            ), 1, null));
        }
        
        if(!$solution_info = $this->model('solution')->get_solution_info_by_question_id($_POST['question_id']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('这个问题没有答案！')));
        }

        if($_GET['ask'])
        {
            $url = get_js_url('/solution/view/' . $solution_info['question_id']);
        }
        else
        {
            $url = get_js_url('/solution/view/' . $solution_info['question_id'] . '?ask=1');
        }

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => $url
        ), 1, null));
    }
}
