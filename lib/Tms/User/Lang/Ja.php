<?php
/**
 * This file is part of Tak-Me System.
 *
 * Copyright (c)2016 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace Tms\User\Lang;

/**
 * Japanese Languages for Tms.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class Ja extends \Tms\Lang\Ja
{
    /**
     * Confirmation.
     */
    protected $CONFIRM_SENDMAIL = 'ユーザーにメールで通知します。よろしいですか？';

    /**
     * Mail.
     */
    protected $REISSUED_MAIL_SUBJECT = 'パスワード再発行通知';
    protected $SUCCESS_REISSUED_MAIL = 'パスワード再発行通知をユーザーに送信しました';
    protected $FAILED_REISSUED_MAIL = 'パスワード再発行通知の送信に失敗しました';
}
