  {% block dropdown_contents %}
  <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
    <li><a href="{{ path_for('profile') }}">{{ user.email }}</a></li>
    <li class="{{ path_for('password.change')|isActive }}"><a href="{{ path_for('password.change') }}">Change password</a></li>
    <li class="{{ path_for('profile')|isActive }}"><a href="{{ path_for('profile') }}">Update Profile</a></li>
    <li role="separator" class="divider"></li>
    <li><a href="{{ path_for('logout') }}">Logout</a>  </li>
  </ul>
  {% endblock dropdown_contents %}
  {% block challenges_dropdown_contents %}
  <ul class="dropdown-menu" aria-labelledby="challengesdropdownmenu">
    <li class="{{ path_for('challenges.home')|isActive }}"><a href="{{ path_for('challenges.home') }}">Current challenges</a></li>
    <li class="{{ path_for('challenges.my')|isActive }}"><a href="{{ path_for('challenges.my') }}">My challenges</a></li>
    <li class="{{ path_for('challenge.create')|isActive }}"><a href="{{ path_for('challenge.create') }}">Create challange</a>  </li>
  </ul>
  {% endblock challenges_dropdown_contents %}

  {% block admin_dropdown_contents %}
  <ul class="dropdown-menu" aria-labelledby="admindropdownmenu">
    <li class=""><a href="#">Admin Users</a></li>
    <li class=""><a href="#">League settings</a></li>
    <li class=""><a href="#">Divisions</a></li>
  </ul>
  {% endblock admin_dropdown_contents %}

  {% block members_dropdown_contents %}
  <ul class="dropdown-menu" aria-labelledby="membersdropdownmenu">
    <li class="{{ path_for('club_members')|isActive }}">
      <a href="{{ path_for('club_members') }}">Club members</a>
    </li>
    <li class="{{ path_for('league_members')|isActive }}">
      <a href="{{ path_for('league_members') }}">League members</a>
    </li>
  </ul>
  {% endblock members_dropdown_contents %}
