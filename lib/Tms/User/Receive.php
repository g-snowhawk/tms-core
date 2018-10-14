<?php
/**
 * This file is part of Tak-Me System.
 *
 * Copyright (c)2016 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace Tms\User;

/**
 * User management request receive class.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class Receive extends Response
{
    /**
     * Save the data receive interface.
     */
    public function save()
    {
        if (parent::save()) {
            $this->session->param('messages', \P5\Lang::translate('SUCCESS_SAVED'));
            $url = $this->app->systemURI().'?mode=user.response';
            if ($this->request->param('profile') === '1') {
                $url .= ':profile';
            }
            \P5\Http::redirect($url);
        }
        $this->edit();
    }

    /**
     * Remove the data receive interface.
     */
    public function remove()
    {
        if (parent::remove()) {
            $this->session->param('messages', \P5\Lang::translate('SUCCESS_REMOVED'));
        }
        \P5\Http::redirect($this->app->systemURI().'?mode=user.response');
    }

    /**
     * Save user alias.
     */
    public function saveAlias()
    {
        $message = 'SUCCESS_SAVED';
        $status = 0;
        $options = [];
        $response = [[$this, 'redirect'], 'user.response:profile'];

        if (false === parent::saveAlias()) {
            $message = 'FAILED_SAVE';
            $status = 1;
            $options = [
                [[$this->view, 'bind'], ['err', $this->app->err]],
            ];
            $response = [[$this, 'editAliasSubform'], null];
        }

        $this->postReceived(\P5\Lang::translate($message), $status, $response, $options);

        //if (parent::saveAlias()) {
        //    $this->session->param('messages', \P5\Lang::translate('SUCCESS_SAVED'));
        //    $url = $this->app->systemURI().'?mode=user.response:profile';
        //    \P5\Http::redirect($url);
        //}
        //$this->edit();
    }
}
