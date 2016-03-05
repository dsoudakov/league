{% use 'menu.inc' %}
{% block nav %}
    <nav class="navbar navbar-default navbar-fixed-top" role="navigation">
      <div class="container-fluid">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#menu-1">
             <span class="sr-only">Menu</span>
             <span class="icon-bar"></span>
             <span class="icon-bar"></span>
             <span class="icon-bar"></span>
          </button> <a class="navbar-brand" href="{{SITEROOT}}">FHTL</a>
        </div>

        <div class="collapse navbar-collapse" id="menu-1">
          <ul class="nav navbar-nav">
            {% if auth.isAdmin() %}
              <li class="dropdown">
                 <a href="#" class="dropdown-toggle" data-toggle="dropdown">Members<strong class="caret"></strong></a>
                 {% block members_dropdown_contents %}
                  {{parent()}}
                 {% endblock members_dropdown_contents %}
              </li>
            {% endif %}
            <li class="dropdown">
               <a href="#" class="dropdown-toggle" data-toggle="dropdown">Challenges<strong class="caret"></strong></a>
               {% block challenges_dropdown_contents %}
                {{parent()}}
               {% endblock challenges_dropdown_contents %}
            </li>
            <li class="{{ path_for('challenge.report')|isActive }}">
              <a href="{{ path_for('challenge.report') }}">Report</a>
            </li>
            <li class="{{ path_for('standings')|isActive }}">
              <a href="{{ path_for('standings') }}">Standings</a>
            </li>
            {% if auth.isAdmin() %}
              <li class="{{ path_for('upload')|isActive }}">
                <a href="{{ path_for('upload') }}">Upload</a>
              </li>
            {% endif %}
          </ul>
          <ul class="nav navbar-nav navbar-right">
            <li><a>Users online: <span class="badge" data-toggle="tooltip" title="{{listOfUsersOnline()|raw}}">{{numOfUsersOnline()}}</span></a></li>
            {% if auth.isAdmin() %}
            <li class="dropdown">
               <a href="#" class="dropdown-toggle" data-toggle="dropdown">Admin<strong class="caret"></strong></a>
                 {% block admin_dropdown_contents %}
                  {{parent()}}
                 {% endblock admin_dropdown_contents %}
            </li>
            {% endif %}
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