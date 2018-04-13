{% set pager_format = pager.format %}
{% set pager_total = pager.total %}
{% set pager_current = pager.current %}
{% set pager_prev = pager.prev %}
{% set pager_next = pager.next %}
{% if pager_total > 1 %}
  {% for i in 1..pager_total %}
    {% if loop.first %}
      <div class="pager">
        {% if pager_prev > 0 %}
          <a href="{{ pager_format|format(pager_prev) }}" class="prev">前へ</a>
        {% else %}
          <span class="prev">前へ</span>
        {% endif %}
      {% endif %}
      {% if pager_current != i %}
        <a href="{{ pager_format|format(i) }}">{{ i }}</a>
      {% else %}
        <b>{{ i }}</b>
      {% endif %}
      {% if loop.last %}
        {% if pager_next <= pager_total %}
          <a href="{{ pager_format|format(pager_next) }}" class="next">次へ</a>
        {% else %}
          <span class="next">次へ</span>
        {% endif %}
      </div>
    {% endif %}
  {% endfor %}
{% endif %}
