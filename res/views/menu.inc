<div class="dropdown">
  <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
    Profile
    <span class="caret"></span>
  </button>
  {% block dropdown_contents %}
  <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
    <li><a href="{{ path_for('profile') }}">{{ user.email }}</a></li>
    <li><a href="{{ path_for('password.change') }}">Change password</a></li>
    <li><a href="{{ path_for('profile') }}">Update Profile</a></li>
    <li></li>
    <li role="separator" class="divider"></li>
    <li><a href="{{ path_for('logout') }}">Logout</a>  </li>
  </ul>
  {% endblock dropdown_contents %}
  {% block challenges_dropdown_contents %}
  <ul class="dropdown-menu" aria-labelledby="challengesdropdownmenu">
    <li><a href="{{ path_for('challenges.home') }}">Current challenges</a></li>
    <li><a href="{{ path_for('challenges.my') }}">My challenges</a></li>
    <li><a href="{{ path_for('challenge.create') }}">Create challange</a>  </li>
  </ul>
  {% endblock challenges_dropdown_contents %}  
</div>