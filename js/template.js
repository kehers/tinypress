var app = app || {};

$(function(){
	app.Template = Backbone.Model.extend({
		defaults: {
            checked: false
        }
	});
	var _Template = Backbone.Collection.extend({
		model: app.Template
	});
	window.app.Templates = new _Template();
	app.TemplateView = Backbone.View.extend({
		tagName: 'li',
		events: {
			'click .screenshot-link':'check'
		},
		initialize: function() {
            this.model.on('change:checked', this.render, this);
        },
        template: _.template($('#template-template').html()),
		render: function() {
			this.$el.html(this.template(this.model.toJSON()));
            
            // Configuration
            if (this.model.get('checked')) {
                // Update config
                var _config = this.model.get('config');
                _.each(_config, function(v){
                    var view = new app.ConfigView({model: v});
                    $('#config').append(view.render().el);
                });
            }
            
			return this;
		},
        
        check: function(){
            // Remove all checked
            app.Templates.each(this.uncheckAll, this);
            // Clear Config
            $('#config').html('');
            // Check
            this.model.set('checked', !this.model.get('checked'));
            $('input[name=template]').val(this.model.get('id'));
            
            return false;
        },
        uncheckAll: function(model){
            /*if (model == this.model)
                return;//*/
            model.set('checked', false);
        }
    });
    
    // Config view
	app.ConfigView = Backbone.View.extend({
        template: _.template($('#config-template').html()),
        render: function(){
            this.$el.html(this.template(this.model));
            return this;
        }
    })
    
    
	// Core
	app.AppView = Backbone.View.extend({
		el: 'body',
		events: {
            '#setup-form submit':'submit'
		},
		initialize: function(){
            window.app.Templates.on('reset', this.reset, this);
		},
        submit: function(){
            
            return false;
        },
		reset: function(){
			app.Templates.each(this.addOne, this);
            // $('.screenshot-link', $('#template li').first()).trigger('click'); // or...
            $('#template li:first-child .screenshot-link').trigger('click');
		},
        addOne: function(event){
			var view = new app.TemplateView({model: event});
			$('#template').append(view.render().el);
		}
	});

});
