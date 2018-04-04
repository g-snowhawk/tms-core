<form id="TMS-subform" action="{{ form.action }}" method="{{ form.method }}" enctype="{{ form.enctype }}"{% if form.confirm %} data-confirm="{{ form.confirm|url_encode }}"{% endif %}>
  <input type="hidden" name="stub" value="{{ stub }}">
  {% block main %}{% endblock %}
</form>
<div id="cancel-subform">
  <a href="#container" id="cancel-subform-button">キャンセル</a>
</div>
