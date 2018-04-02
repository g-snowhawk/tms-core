<section id="user_alias" class="relational-list">
  <h2>代替アカウント</h2>
  <nav>
    <ul>
    {% for alias in aliases %}
      <li>
        <div class="line">
          <a href="?mode=user.response:editAlias&amp;id={{ alias.id }}">
            <span>{{ alias.fullname }}</span>
            <span>{{ alias.email }}</span>
          </a>
          <label class="checkmark">削除<input type="checkbox" name="remove[{{ alias.id }}]"><span>✓</span></label>
        </div>
      </li>
    {% endfor %}
    <ul>
  </nav>
  <p><a href="?mode=user.response:editAlias">+代替アカウント追加</a></p>
</section>
