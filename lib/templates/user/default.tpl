{% extends "master.tpl" %}

{% block main %}
      <input type="hidden" name="mode" value="user.receive:remove">
      <div class="list">
        <h1 class="headline">登録ユーザー一覧</h1>
        <div class="x-scroller">
          <table>
            <thead>
              <tr>
                <td>フルネーム</td>
                <td>E-mail</td>
                <td class="button"> </td>
                <td class="button"> </td>
                <td class="button"> </td>
              </tr>
            </thead>
            <tbody>
{% for unit in users %}
              <tr>
                <td>{{ unit.company }}</td>
                <td>{{ unit.email }}</td>
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
                <td class="button reddy"><label><input type="radio" name="delete" value="{{ unit.id }}">削除</label></td>
{% else %}
                <td class="button reddy">&nbsp;</td>
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
          <div class="footer-ctrl">
{% if apps.hasPermission('user.create') %}
            <a href="?mode=user.response:edit">＋新規ユーザー</a>
{% else %}
            <span class="dummy">&nbsp;</span>
{% endif %}
          </div>
        </div>
      </div>
{% endblock %}
