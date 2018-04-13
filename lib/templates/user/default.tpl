{% extends "master.tpl" %}

{% block head %}
  <script src="script/fix_thead_vertical_scroll.js"></script>
{% endblock %}

{% block main %}
  <input type="hidden" name="mode" value="user.receive:remove">
  <div class="explorer-list">
    <h1 class="headline">登録ユーザー一覧</h1>
    <table class="ftv-table">
      <thead>
        <tr>
          <td>フルネーム</td>
          <td>E-mail</td>
          <td> </td>
          <td> </td>
          <td> </td>
        </tr>
      </thead>
      <tbody>
        {% for unit in users %}
          <tr>
            <td>{{ unit.company }}</td>
            <td class="spacer">{{ unit.email }}</td>
            {% if apps.hasPermission('system') %}
              <td class="button"><a href="?mode=user.response:switchUser&id={{ unit.id|url_encode }}">切替</a></td>
            {% else %}
              <td class="button">&nbsp;</td>
            {% endif %}
            {% if apps.hasPermission('user.update') %}
              <td class="button"><a href="?mode=user.response:edit&id={{ unit.id|url_encode }}">編集</a></td>
            {% else %}
              <td class="button">&nbsp;</td>
            {% endif %}
            {% if apps.hasPermission('user.delete') %}
              <td class="reddy button"><label><input type="radio" name="delete" value="{{ unit.id }}">削除</label></td>
            {% else %}
              <td class="reddy button">&nbsp;</td>
            {% endif %}
          </tr>
        {% else %}
          <tr>
            <td class="nowrap" colspan="2">ユーザーの登録がありません</td>
            <td></td>
            <td></td>
          </tr>
        {% endfor %}
      </tbody>
    </table>
    <div class="footer-controls">
      <nav class="links">
        {% if apps.hasPermission('user.create') %}
          <a href="?mode=user.response:edit">＋新規ユーザー</a>
        {% else %}
          <span class="dummy">&nbsp;</span>
        {% endif %}
      </nav>
      <nav class="pagination">
        {% include 'pagination.tpl' %}
      </nav>
    </div>
  </div>
{% endblock %}
