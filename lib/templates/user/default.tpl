{% extends "master.tpl" %}

{% block main %}
  <input type="hidden" name="mode" value="user.receive:remove">
  <div class="explorer-list">
    <h1 class="headline">登録ユーザー一覧</h1>
    <div class="explorer-body">
    <table>
      <thead>
        <tr>
          <td>フルネーム</td>
          <td>所属</td>
          <td>E-mail</td>
          <td>登録日</td>
          <td>更新日</td>
          {% if apps.isRoot %}
            <td>&nbsp;</td>
          {% endif %}
          <td>&nbsp;</td>
          <td>&nbsp;</td>
        </tr>
      </thead>
      <tbody>
        {% for unit in users %}
          <tr>
            <td>{{ unit.fullname }}</td>
            <td>{{ unit.company }}</td>
            <td class="spacer">{{ unit.email }}</td>
            <td class="date">{{ unit.create_date|date('Y年n月j日 H:i') }}</td>
            <td class="date">{{ unit.modify_date|date('Y年n月j日 H:i') }}</td>
            {% if apps.isRoot %}
              <td class="button"><a href="?mode=user.response:switchUser&id={{ unit.id|url_encode }}">切替</a></td>
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
            <td class="nowrap empty" colspan="5">ユーザーの登録がありません</td>
            <td></td>
            <td></td>
            <td></td>
          </tr>
        {% endfor %}
      </tbody>
    </table>
    </div>
    <div class="footer-controls">
      <nav class="links">
        {% if apps.hasPermission('user.create') %}
          <a href="?mode=user.response:edit"><i class="mark">+</i>新規ユーザー</a>
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
