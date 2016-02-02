<input type="hidden" name="{{ app.getContainer().get('csrf').getTokenNameKey() }}" 
value="{{ app.csrf_name }}">
<input type="hidden" name="{{ app.getContainer().get('csrf').getTokenValueKey() }}" 
value="{{ app.csrf_value }}">