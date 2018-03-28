/**
 * Javascript Library for Tak-Me CMS
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 *
 * @copyright 2017 PlusFive (https://www.plus-5.com)
 * @version 1.0.0
 */
if(TM.getCookie('enableCookie') == 'yes'){
    document.write('<input type="submit" name="authEnabler" value="サインイン">');
} else {
    document.write('<div class="err"><p>Cookieを有効にして、ページを再読込みしてください</p></div>');
}
