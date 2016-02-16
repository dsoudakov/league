<input  ng-init="{{ option1 }}csrf_name='{{ app.csrf_name }}'" 
		
		type="hidden" 
		name="{{ app.getContainer().get('csrf').getTokenNameKey() }}" 
		value="{{ app.csrf_name }}">

<input  ng-init="{{ option2 }}csrf_value='{{ app.csrf_value }}'" 
		
		type="hidden" 
		name="{{ app.getContainer().get('csrf').getTokenValueKey() }}" 
		value="{{ app.csrf_value }}">