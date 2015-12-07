_.templateSettings = {
  evaluate:    /\{\{#([\s\S]+?)\}\}/g,
  interpolate: /\{\{[^#\{]([\s\S]+?)[^\}]\}\}/g,
  escape:      /\{\{\{([\s\S]+?)\}\}\}/g,
}

Backbone.emulateHTTP = true;

var app = app || {};

// Notification
app.nmodel = Backbone.View.extend({
    tagName: 'li',
    events: {
        'click .fa-times': 'close'
    },
    template: _.template('<span{{ success ? \' class="success"\' : \'\' }}>{{ msg }} <i class="fa fa-times"></i></span>'),
    close: function(){
        this.$el.fadeOut('slow', function(){
            $(this).remove();
        });
    },
    render: function(){
        this.$el.html(this.template(this.model));
        setTimeout(function(){
            this.$('.fa-times').trigger('click');
        }, 20000);
        return this;
    }
});
app.notify = function(msg, success){
    var view = typeof success == 'undefined' ?
                    new app.nmodel({model: {msg: msg, success: false}}) : 
                    new app.nmodel({model: {msg: msg, success: success}});
    $('.gritter').prepend(view.render().el);
};