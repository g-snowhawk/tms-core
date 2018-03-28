{% if block('head') is defined %}
  {% include 'header.tpl' with {head: block('head')} %}
{% else %}
  {% include 'header.tpl' %}
{% endif %}
{% if messages is not empty %}
  <body data-loadmessage="{{ messages|url_encode }}">
{% endif %}
  <header id="global-nav">
    <nav>
      <ul class="navroot">
        <li id="nav-00">
          <span class="logo">Tak-Me <span>System</span></span>
          <a href="?mode=user.response.response:profile">ID: {{ apps.userinfo.uname }}</a>
          <ul>
            {% set template = apps.currentApp ~ "/private_nav.tpl" %}
            {% if apps.view.exists(template) %} 
              {% include template %}
            {% endif %}
            <li><a href="?mode=user.response:profile">プロフィール編集</a></li>
            <li><hr><a href="?logout" id="signout">サインアウト</a></li>
          </ul>
        </li>
        {% if nav|length > 1 %} 
          {% for item in nav %}
            {% if loop.first %}
              <li id="nav-application"><a href="?mode=user.response">■</a>
                <ul>
            {% endif %}
              <li><a href="?mode=system.response:change&amp;app={{ item.code }}">{{ item.name }}</a></li>
            {% if loop.last %}
                </ul>
              </li>
            {% endif %}
          {% endfor %}
        {% endif %}
        {% set template = apps.currentApp ~ "/application_nav.tpl" %}
        {% if apps.view.exists(template) %} 
          {% include template %}
        {% endif %}
        {% if apps.hasPermission('system') %} 
          <li id="nav-system"><a href="?mode=user.response">システム管理</a>
            <ul>
              {% set template = apps.currentApp() ~ "/system_nav.tpl" %}
              {% if apps.view.exists(template) %} 
                {% include template %}
              {% endif %}
              {% if apps.hasPermission('user.read') %} 
                <li><a href="?mode=user.response">ユーザー管理</a></li>
              {% endif %}
              <li><a href="?mode=system.response">拡張機能管理</a></li>
              <li><a href="?mode=system.response:plugins">プラグイン管理</a></li>
              <li><a href="?mode=system.response:log">システムログ</a></li>
            </ul>
          </li>
        {% endif %}
      </ul>
    </nav>
  </header>
  <div id="container">
    <form id="TMS-mainform" action="{{ form.action }}" method="{{ form.method }}" enctype="{{ form.enctype }}"{% if form.confirm %} data-confirm="{{ form.confirm|url_encode }}"{% endif %}>
      <input type="hidden" name="stab" value="{{ stab }}">
      {% block main %}{% endblock %}
      {% include 'nochange.tpl' %}
    </form>
    {% block subform %}{% endblock %}
  </div>
