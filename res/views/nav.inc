{% use 'menu.inc' %}
{% block nav %}
    <nav class="navbar navbar-default navbar-fixed-top" role="navigation">
      <div class="container-fluid">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
             <span class="sr-only">Menu</span>
             <span class="icon-bar"></span>
             <span class="icon-bar"></span>
             <span class="icon-bar"></span>
          </button> <a class="navbar-brand" href="{{SITEROOT}}/public/">FHTL</a>
        </div>
        
        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
          <ul class="nav navbar-nav">
            <li class="{{ path_for('profile')|isActive }}">
              <a href="{{ path_for('profile') }}">Profile</a>
            </li>
            <li class="{{ path_for('home')|isActive }}">
              <a href="{{ path_for('home') }}">Home</a>
            </li>
            <li class="{{ path_for('players')|isActive }}">
              <a href="{{ path_for('players') }}">Players</a>
            </li>
            <li class="dropdown">
               <a href="#" class="dropdown-toggle" data-toggle="dropdown">Challenges<strong class="caret"></strong></a>
               {% block challenges_dropdown_contents %}
                {{parent()}}
               {% endblock challenges_dropdown_contents %}
            </li>

            {% if auth.isAdmin() %}

            <li class="{{ path_for('upload')|isActive }}">
              <a href="{{ path_for('upload') }}">Upload</a>
            </li>
            {% endif %}

          </ul>
          <form class="navbar-form navbar-left" role="search">
            <div class="form-group">
              <input type="text" class="form-control" />
            </div> 
            <button type="submit" class="btn btn-default">
              Find player
            </button>
          </form>
          <ul class="nav navbar-nav navbar-right">
            <li>
              <a href="#">Schedule</a>
            </li>
            <li class="dropdown">
               <a href="#" class="dropdown-toggle" data-toggle="dropdown"><strong class="glyphicon glyphicon-home">
               </strong><strong class="caret"></strong></a>
               {% block dropdown_contents %}
                {{parent()}}
               {% endblock dropdown_contents %}
            </li>
          </ul>
        </div>
      </div>
    </nav>

{% endblock nav %}