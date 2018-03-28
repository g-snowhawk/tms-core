<!DOCTYPE html>
<html lang="ja"{% if header.class is defined %} class="{{ header.class }}"{% endif %} id="{{ header.id }}">
  {% if header.id != 'signin' %}
    <noscript>
      <meta http-equiv="refresh" content="1; URL=?logout">
    </noscript>
  {% endif %}
  <meta charset="utf-8">
  <meta name="robots" content="noindex,nofollow">
  <title>Tak-Me System: {{ header.title }}</title>
  <link rel="stylesheet" href="style/default.css">
  <script src="script/common.js" charset="utf-8"></script>
  {% set template = apps.currentApp ~ "/html_header.tpl" %}
  {% if apps.view.exists(template) %} 
    {% include template %}
  {% endif %}
  {{ head|raw }}
