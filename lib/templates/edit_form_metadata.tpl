<div class="metadata">
  {% if revision is defined %}
    <span class="justify">公開</span>：{% if revision is not empty %}Version {{ revision }}{% else %}なし{% endif %}<br>
  {% endif %}
  登録日：{{ post.create_date|date('Y年n月j日 H:i') }}<input type="hidden" name="create_date" value="{{ post.create_date }}"><br>
  更新日：{{ post.modify_date|date('Y年n月j日 H:i') }}<input type="hidden" name="modify_date" value="{{ post.modify_date }}"><br>
  {% if post.author_date is defined %}
    編集日：<input type="datetime-local" name="author_date" id="author_date" value="{{ post.author_date|date('Y-m-d\\TH:i') }}" class="normal">
  {% endif %}
</div>
