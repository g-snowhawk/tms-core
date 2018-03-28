{% extends "master.tpl" %}

{% block main %}
  <div class="wrapper">
    <h1>システムログ</h1>
    <div class="hgroup">
      <h2>エラーログ</h2>
      <p><label><input type="checkbox" name="errorlog_rotate" value="1">Rotation</label></p>
    </div>
    <div class="log-block">
      {{ source(errorlog, ignore_missing = true)|nl2br }}
    </div>
    <div class="hgroup">
      <h2>アクセスログ</h2>
      <p><label><input type="checkbox" name="accesslog_rotate" value="1">Rotation</label></p>
    </div>
    <div class="log-block">
      {{ source(accesslog, ignore_missing = true)|nl2br }}
    </div>
    <div class="form-footer">
      <input type="submit" name="s1_submit" value="実行">
      <input type="hidden" name="mode" value="system.receive:logRotate">
    </div>
  </div>
{% endblock %}
