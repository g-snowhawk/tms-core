{% extends "master.tpl" %}

{% block head %}
  <script src="script/subform.js"></script>
  <script src="script/user_alias.js"></script>
{$ endblock %}

{% block main %}
  {% if post.profile != 1 %}
    <p id="backlink"><a href="?mode=user.response">一覧に戻る</a></p>
  {% endif %}
  <div class="wrapper">
    <h1>ユーザーデータ編集</h1>
    {% if err.vl_fullname == 1 %}
      <div class="error">
        <i>入力してください</i>
      </div>
    {% endif %}
    <div class="fieldset{% if err.vl_fullname == 1 %} invalid{% endif %}">
      <label for="fullname"><sup class="necessary">(必須)</sup>フルネーム</label>
      <input type="text" name="fullname" id="fullname" value="{{ post.fullname }}" data-validate="necessary">
    </div>
    <div class="fieldset">
      <label for="company">会社名</label>
      <input type="text" name="company" id="company" value="{{ post.company }}">
    </div>
    {% if err.vl_email == 1 %}
      <div class="error">
        <i>入力してください</i>
      </div>
    {% endif %}
    <div class="fieldset{% if err.vl_email == 1 %} invalid{% endif %}">
      <label for="email"><sup class="necessary">(必須)</sup>E-mail</label>
      <input type="text" name="email" id="email" value="{{ post.email }}" data-validate="necessary">
    </div>
    <div class="fieldset">
      <label for="url">URL</label>
      <input type="text" name="url" id="url" value="{{ post.url }}">
    </div>
    <div class="fieldset">
      <label for="zip">郵便番号</label>
      <input type="text" name="zip" id="zip" value="{{ post.zip }}">
    </div>
    <div class="fieldset">
      <label for="state">都道府県</label>
      <input type="text" name="state" id="state" value="{{ post.state }}">
    </div>
    <div class="fieldset">
      <label for="city">市区郡</label>
      <input type="text" name="city" id="city" value="{{ post.city }}">
    </div>
    <div class="fieldset">
      <label for="town">町村名</label>
      <input type="text" name="town" id="town" value="{{ post.town }}">
    </div>
    <div class="fieldset">
      <label for="address1">番地等</label>
      <input type="text" name="address1" id="address1" value="{{ post.address1 }}">
    </div>
    <div class="fieldset">
      <label for="address2">建物名等</label>
      <input type="text" name="address2" id="address2" value="{{ post.address2 }}">
    </div>
    <div class="fieldset">
      <label for="tel">電話番号</label>
      <input type="text" name="tel" id="tel" value="{{ post.tel }}">
    </div>
    <div class="fieldset">
      <label for="fax">FAX番号</label>
      <input type="text" name="fax" id="fax" value="{{ post.fax }}">
    </div>
    {% if post.id is empty %}
      {% if err.vl_uname == 1 %}
            <div class="error">
              <i>入力してください</i>
            </div>
      {% endif %}
      <div class="fieldset{% if err.vl_uname == 1 %} invalid{% endif %}">
        <label for="uname"><sup class="necessary">(必須)</sup>ログイン名</label>
        <input type="text" placeholder="半角英数字" name="uname" id="uname" value="{{ post.uname }}" data-validate="necessary">
      </div>
    {% endif %}

    {% if apps.hasPermission('root') and (apps.isParent(post.id) or post.id is empty) %} 
      <div class="fieldset">
        <div class="legend">管理者権限</div>
        <div class="input">
          <label><input type="checkbox" name="admin" value="1"{% if post.admin == '1' %} checked{% endif %}>システム管理を許可する</label>
        </div>
      </div>
    {% endif %}

    <script src="script/tms_user.js" charset="utf-8"></script>
    <h2><a href="#update-password" class="accordion-switcher" data-callback="erasePassword">パスワード{% if post.id is empty %}設定{% else %}変更{% endif %}</a></h2>
    <div id="update-password" class="accordion">
      <div class="fieldset{% if err.vl_upass == 1 %} invalid{% endif %}">
        <label for="upass">パスワード</label>
        <input type="password" placeholder="半角英数字" name="upass" id="upass" autocomplete="off">
      </div>
      <div class="fieldset{% if err.vl_retype == 1 %} invalid{% endif %}">
        <label for="retype">再入力</label>
        <input type="password" placeholder="半角英数字" name="retype" id="retype" autocomplete="off">
      </div>
    </div>

    {% if post.profile != 1 %}
      {% include 'user/permission.tpl' %}
      {% set template = apps.currentApp() ~ "/permission.tpl" %}
      {% if apps.view.exists(template) %} 
        {% include template %}
      {% endif %}
    {% elseif not apps.hasPermission('root') and apps.app.cnf('global:enable_user_alias') == '1' %} 
      {% include 'user/alias_list.tpl' %}
    {% endif %}

    <div class="metadata">
      登録日：{{ post.create_date|date('Y年n月j日 H:i') }}<input type="hidden" name="create_date" value="{{ post.create_date }}"><br>
      更新日：{{ post.modify_date|date('Y年n月j日 H:i') }}<input type="hidden" name="modify_date" value="{{ post.modify_date }}"><br>
    </div>
    <div class="form-footer">
      <input type="submit" name="s1_submit" value="登録">
      <input type="hidden" name="mode" value="user.receive:save">
      <input type="hidden" name="id" value="{{ post.id }}">
      {% if post.profile == 1 %}
        <input type="hidden" name="profile" value="{{ post.profile }}">
      {% endif %}
    </div>
  </div>
{% endblock %}
