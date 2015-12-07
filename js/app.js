var app = app || {};

$(function(){

	if (typeof String.prototype.endsWith !== 'function') {
	    String.prototype.endsWith = function(suffix) {
	        return this.indexOf(suffix, this.length - suffix.length) !== -1;
	    };
	}
    
    String.prototype.hashCode = function() {
      var hash = 0, i, chr, len;
      if (this.length == 0) return hash;
      for (i = 0, len = this.length; i < len; i++) {
        chr   = this.charCodeAt(i);
        hash  = hash*31 + chr;
        hash |= 0;
      }
      return hash;
    };

	/*Backbone.sync = function(method, model, options) {
		console.log(method);
		console.log(model);
		console.log(options);
	}//*/
    
    app.autoSaveID = null;
    var actionButtons = {
        save: 'Save draft on Github',
        preview: 'Preview',
        post: 'Post',
        later: 'Post later...'
    };
    app.lastSaveHash = 0;
    app.autoSave = function(){    
        // Auto save
        // 1. Clear any existing saver
        if (app.autoSaveID)
            clearTimeout(app.autoSaveID);
        app.autoSaveID = setInterval(function(){
            // Still on post view?
            if (!$('.new-post-view').is(':visible')
                /* || app.options.paid == 0*/) {
                // No, stop autosave and return
                clearTimeout(app.autoSaveID);
                return;
            }
            var id = $('input[name=save_id]').val();
            var title = $('input[name=title]').val();
            var body = $('textarea').val();
            
            // No empty content
            if (title.trim() == '' && body.trim() == '')
                return;
                
            var serialized_data = 'title='+title+'&body='+body+'&id='+id;
            var thisHash = serialized_data.hashCode();
            if (thisHash == app.lastSaveHash) return;
            app.lastSaveHash = thisHash;
            $.post('/api/drafts', serialized_data, function(data){
                if (data.done) {
                    // Autosave
                    // drafts.add
                    window.app.Drafts.add({
                            id: data.id,
                            title: title,
                            body: body,
                            date: data.date
                        }, {merge: true});
                    if (id == '') {
                        // new 
                        // save id
                        $('input[name=save_id]').val(data.id);
                    }
                }
            }, 'json');
        }, 5000); // 5 seconds. Overkill?
    }

	// Interaction/Model
	app.Post = Backbone.Model.extend({
		defaults: {
            'later':false
        },
	    sync: function (method, model, options) {
	        if (method === 'delete') {
	            options = options || {};
	            options.contentType = 'application/json';
	            options.data = JSON.stringify(this.toJSON());
	        }

	        return Backbone.sync.call(this, method, model, options);
	    }
	});
	app.Draft = Backbone.Model.extend({
		defaults: {},
	    sync: function (method, model, options) {
	        if (method === 'delete') {
	            options = options || {};
	            options.contentType = 'application/json';
	            options.data = JSON.stringify(this.toJSON());
	        }

	        return Backbone.sync.call(this, method, model, options);
	    }
	});
	// Interaction/Collection
	var _Posts = Backbone.Collection.extend({
		model: app.Post,
		url: app.options.path+'/api/get',
        set: function(models, options) {
            var res = Backbone.Collection.prototype.set.call(this, models, options);
            app.options.more = false;
            return res;
        }
	});
	window.app.Posts = new _Posts();
    var _Drafts = Backbone.Collection.extend({
		model: app.Draft,
		url: '/api/drafts'
	});
	window.app.Drafts = new _Drafts();
	// Interaction/View
	app.PostView = Backbone.View.extend({
		tagName: 'li',
		events: {
			'click .delete':'delete',
			'click .edit':'edit'
		},
		initialize: function() {},
		template: _.template($('#post-template').html()),
		render: function() {
			this.$el.html(this.template(this.model.toJSON()));
			return this;
		},
		edit: function() {
            /*if (app.options.paid == 0) {
                // edir to settings -> pay view
                app.notify("Upgrade your account to continue");
                $('a.settings').trigger('click');
                return false;
            }*/
            
			// Get
			// Show view
			$(".post-form")[0].reset();
			$("input[type=hidden]").val('');

			$('.posts-view').hide();
			$('.new-post-view').show();

			// load content
			NProgress.start();
			var sha = this.model.get('sha');
            var keyPath = this.model.get('path');
            if (this.model.get('later'))
                keyPath += '&scheduled=1';
			$.get(app.options.path+'/api/get?key='+keyPath, function(data){
				NProgress.done();
				if (data.error) {
					app.notify(data.error);
					Backbone.history.navigate('/');
				}
				else {
					document.title = data.title;

					$('textarea').focus();
					$('input[name=title]').val(data.title);
					$('input[name=url]').val(data.url);
					$('input[name=sha]').val(sha);
					$('textarea').val(data.body);
					$('input[name=tags]').val(data.tags);
					$('input[name=categories]').val(data.categories);
					$('input[name=commit]').val('Post update: '+data.title);
					$('input[name=permalink]').val(data.permalink);

					if (data.draft) {
						$('input[name=action]').val('draft');
						$('.button-grp li button').eq(0).html(actionButtons.save);
						$('.button-grp li button').eq(1).html(actionButtons.preview);
						$('.button-grp li button').eq(2).html(actionButtons.post);
						$('.button-grp li button').eq(3).html(actionButtons.later);
					}
                    else if (data.later) {
                        $('input[name=schedule_id]').val(data.schedule_id);
						$('input[name=action]').val('schedule');
                        $('.tz-overlay input[name=at]').val(data.send_at);
						$('.button-grp li button').eq(0).html(actionButtons.later);
						$('.button-grp li button').eq(1).html(actionButtons.post);
						$('.button-grp li button').eq(2).html(actionButtons.preview);
						$('.button-grp li button').eq(3).html(actionButtons.save);
                    }
				}
			}, 'json');

			var href = '/edit/'+this.model.get('path');
			Backbone.history.navigate(href);
			return false;
		},
		delete: function() {
			if (confirm("Do you really want to delete this post?")) {

				this.model.url = app.options.path+'/api/delete';
				this.model.destroy();
				this.$el.remove();
			}

			return false;
		}
	});
	app.DraftView = Backbone.View.extend({
		tagName: 'li',
		events: {
			'click .delete':'delete',
			'click .post-ttl a':'edit'
		},
		initialize: function() {
            this.model.on('change', this.render, this);
            this.model.on('silent_delete', this._delete, this);
        },
		template: _.template($('#draft-template').html()),
		render: function() {
            //console.log("rerender");
			this.$el.html(this.template(this.model.toJSON()));
			return this;
		},
		edit: function() {
			// Get
			// Show view
			$(".post-form")[0].reset();
			$("input[type=hidden]").val('');

			$('.posts-view').hide();
			$('.new-post-view').show();

            var title = this.model.get('title');
            document.title = title;

            $('textarea').focus();
            $('input[name=save_id]').val(this.model.get('id'));
            $('input[name=title]').val(title);
            $('textarea').val(this.model.get('body'));

            $('.button-grp li button').eq(0).html(actionButtons.preview);
            $('.button-grp li button').eq(1).html(actionButtons.post);
            $('.button-grp li button').eq(2).html(actionButtons.later);
            $('.button-grp li button').eq(3).html(actionButtons.save);

			var href = '/draft-edit/'+this.model.get('id');
			Backbone.history.navigate(href);
            
            app.autoSave();
            
			return false;
		},
		delete: function() {
			if (confirm("Delete this draft?")) {
                this._delete();
			}

			return false;
		},
        _delete: function(){    
            this.model.url = '/api/drafts/delete';
            this.model.destroy();
            this.$el.remove();
        }
	});
	
	// Core
	app.AppView = Backbone.View.extend({
		el: 'body',
		events: {
			'click header h2 a':'redirPostView',
			'click .new':'newPostView',
			'click .settings':'settingsView',
			'click .more a':'more',
            
			//'click .draft':'setDraft', // where?
			'keyup input[name=title]':'createURL',
			'click .post-form button':'submit',
			'click .a-post':'redirPostView',
			'click .a-post':'redirPostView',
			'click .a-drafts':'redirDraftView',
			'click .btn-grp-switch':'toggleButton',
			'click .adv-options-switch':'toggleAdvancedOptions',
            
            // settings
            'submit #cname-form':'updateCNAME',
            'submit #email-form':'updateEmail',
            'submit #timezone-form':'updateTZ',
            'click a.upgrade-btn':'togglePayForm',
            'click a.cancel-pay':'togglePayForm',
            'click a.cancel-renewal-btn':'cancelRenewal',
            'submit form.upgrade-form':'submitUpgradeForm',
            
            // Overlay
            'click .modal-close':'modalClose',
			'submit .tz-overlay form':'schedulePost'
		},
		initialize: function(){
			window.app.Posts.on('reset', this.resetTimeline, this);
			window.app.Posts.on('add', this.addOneEvent, this);
			window.app.Drafts.on('reset', this.resetDraft, this);
			window.app.Drafts.on('add', this.addOneDraft, this);
			window.onpopstate = this.popState.bind(this);
		},
		resetDraft: function(){
			app.Drafts.each(this.addOneDraftReverse, this);
		},
		resetTimeline: function(){
			// clear?
			$('#posts').empty();
            
            // Empty state
            if (app.Posts.length == 0) {
                $('#empty-state').show();
                $('.more').hide();
                return;
            }
            
            $('#empty-state').hide();
            $('.more').show();
            
			app.Posts.each(this.addOneEventReverse, this);
		},
        addOneEvent: function(event){
			var view = new app.PostView({model: event});
            // If from "more" fetch
            if (app.options.more)
                $('#posts').append(view.render().el);
            else
                $('#posts').prepend(view.render().el);
		},
        addOneDraft: function(event){
			var view = new app.DraftView({model: event});
            $('#drafts').prepend(view.render().el);
		},
        addOneDraftReverse: function(event){
			var view = new app.DraftView({model: event});
			$('#drafts').append(view.render().el);
		},
        addOneEventReverse: function(event){
			var view = new app.PostView({model: event});
			$('#posts').append(view.render().el);
		},
        more: function(){
            // Active, try again later
            if (app.options.more)
                return;

            app.options.more = true;
            NProgress.start();
            
            app.options.page++;
            // fetch new from server
            app.Posts.fetch({data: {page: app.options.page}, success: function(){
                NProgress.done();
            }});
            return false;
        },
		newPostView: function(){
            /*// Paid or not
            if (app.options.paid == 0) {
                // edir to settings -> pay view
                app.notify("Upgrade your account to continue");
                $('a.settings').trigger('click');
                return false;
            }*/
            
			// view
			document.title = 'New post';
			this.$('.posts-view').hide();
			this.$('.new-post-view').show();
			this.$('.settings-view').hide();
            
            // Preview first
            $('.button-grp li button').eq(0).html(actionButtons.preview);
            $('.button-grp li button').eq(1).html(actionButtons.post);
            $('.button-grp li button').eq(2).html(actionButtons.later);
            $('.button-grp li button').eq(3).html(actionButtons.save);
            
			var href = '/new';
			Backbone.history.navigate(href);
            
            app.autoSave();
            
			return false;
		},
		settingsView: function(){
			document.title = 'Settings';
			this.$('.posts-view').hide();
			this.$('.new-post-view').hide();
            this.$('.settings-view').show();
            
			var href = '/settings';
			Backbone.history.navigate(href);
            
			return false;
		},
        // where is this called?
		/*redirNewPostView: function(){
			this.newPostView();
			Backbone.history.navigate('new');
			return false;
		},//*/
		redirPostView: function(){
			this.postView();
			Backbone.history.navigate('./');
			return false;
		},
        redirDraftView: function(){
			this.draftView();
			Backbone.history.navigate('saves');
			return false;
		},
		draftView: function(){
			document.title = 'Autosaves';

			// reset form only if edit mode
			if (this.$('input[name=sha]').val() != '') {
				$(".post-form")[0].reset();
				$("input[type=hidden]").val('');
			}

			this.$('.new-post-view').hide();
			this.$('.settings-view').hide();
			this.$('.posts-view').show();
            this.$('.posts-view .ttl').html('Autosaves');
            this.$('#drafts').show();
            // hide posts, empty and more
            this.$('#posts').hide();
            this.$('#empty-state').hide();
            this.$('.more').hide();

			return false;
		},
		postView: function(){
			// view
			document.title = 'Posts';

			// reset form only if edit mode
			if (this.$('input[name=sha]').val() != '') {
				$(".post-form")[0].reset();
				$("input[type=hidden]").val('');
			}

			this.$('.new-post-view').hide();
			this.$('.settings-view').hide();
			this.$('.posts-view').show();
            this.$('.posts-view .ttl').html('Posts');
            this.$('#posts').show();
            // hide drafts
            this.$('#drafts').hide();
            // reset emptystate and more
            if (app.Posts.length == 0)
                this.$('#empty-state').show();
            else
                this.$('.more').show();

			return false;
		},
		popState: function(){
            // Hndle back button
			var href = document.location.href;
			if (href.endsWith('new')) {
				this.newPostView();
			}
			else if (href.endsWith('settings')) {
				this.settingsView();
			}
			else if (href.endsWith('saves')) {
				this.redirDraftView();
			}
			else if (href.match(/^edit\//)) {
				//console.log("Do edit");
			}
			else
				this.postView();
		},
		togglePayForm: function(){
			this.$('.upgrade-form').toggle();
			this.$('.upgrade-btn-wrp').toggle();
			this.$('input[name=cc_number]').focus();
			return false;
		},
		toggleAdvancedOptions: function(){
			$swtch = this.$('.adv-options-switch i');

			if ($swtch.hasClass('fa-angle-right')) {
				$swtch.removeClass('fa-angle-right');
				$swtch.addClass('fa-angle-down');
			}
			else {
				$swtch.removeClass('fa-angle-down');
				$swtch.addClass('fa-angle-right');
			}

			this.$('.adv-options').toggle();
			return false;
		},
		toggleButton: function(){
			this.$('.button-grp').toggleClass('opened');
			return false;
		},
		createURL: function(){
			if (this.$('input[name=sha]').val() != '')
				return;

			this.$('input[name=commit]').val('New post: '+this.$('input[name=title]').val());
			var url = app.options.date + '-' + this.$('input[name=title]')
						.val()
						.toLowerCase() // lowercase
						.replace(/[^a-z0-9\_]/g, '-') // non words
						.replace(/\-+/g, '-') // double -
						.replace(/\-$/g, '') // - after
						.replace(/^\-/g, '') // - before
						+ '.markdown';
			this.$('input[name=url]').val(url);
		},
		updateCNAME: function(e){
            var v = $('input[name=cname]').val();
            if (v == '') {
                app.notify("You did not enter a custom domain");
                $('input[name=cname]').focus();
                return false;
            }
            
			NProgress.start();
			$('#cname-form button').addClass('disabled').prop('disabled', true);
			$.post('/api/cname', $('#cname-form').serialize(), function(data){
				NProgress.done();
				$('#cname-form button').removeClass('disabled').prop('disabled', false);
				if (data.done) {
					var msg = 'Custom domain successfully updated. Please note that DNS propagation may take few hours.';
					app.notify(msg, true);
				}
				else {
					$("#cname-form")[0].reset();
					app.notify(data.error);
				}
			}, 'json');
            
			return false;
		},
        updateTZ: function(e){
            var v = $('select[name=timezone]').val();
            var t = $('select[name=timezone] option:selected').text();
            
			NProgress.start();
			$('#timezone-form button').addClass('disabled').prop('disabled', true);
			$.post('/api/timezone', 'tz='+v+'&country='+t, function(data){
				NProgress.done();
				$('#timezone-form button').removeClass('disabled').prop('disabled', false);
				if (data.done) {
                    app.options.tz = 1;
                    $('.schedule-tz').remove();
					var msg = 'Timezone successfully updated.';
					app.notify(msg, true);
				}
				else {
					$("#timezone-form")[0].reset();
					app.notify(data.error);
				}
			}, 'json');
            
			return false;
		},
        updateEmail: function(e){
            var v = $('input[name=email]').val();
            if (v == '') {
                app.notify("You did not enter an email");
                return false;
            }
            
			NProgress.start();
			$('#email-form button').addClass('disabled').prop('disabled', true);
			$.post('/api/email', $('#email-form').serialize(), function(data){
				NProgress.done();
				$('#email-form button').removeClass('disabled').prop('disabled', false);
				if (data.done) {
					var msg = 'Email successfully updated.';
					app.notify(msg, true);
				}
				else {
					$("#email-form")[0].reset();
					app.notify(data.error);
				}
			}, 'json');
            
			return false;
		},
        /*submitUpgradeForm: function(e){
            e.preventDefault();
            
            var $form = $('form.upgrade-form');
            var number = $('input[name=cc_number]').val().trim();
            if(!Stripe.card.validateCardNumber(number)){
                $('input[name=cc_number]').focus();
                app.notify("Invalid card number.");
                return false;
            }
            var month = $('input[name=cc_mm]').val().trim();
            if(!month.length){
                $('input[name=cc_mm]').focus();
                app.notify("Kindly enter the expiry month.");
                return false;
            }
            if(month.length == 1)
                month = '0'+month;
            var year = $('input[name=cc_yy]').val().trim();
            if(!year.length){
                $('input[name=cc_yy]').focus();
                app.notify("Kindly enter the expiry year.");
                return false;
            }
            var ccv = $('input[name=cc_ccv]').val().trim();
            if(!Stripe.card.validateCVC(ccv)){
                $('input[name=cc_ccv]').focus();
                app.notify("Invalid card CCV.");
                return false;
            }
                        
            NProgress.start();
            
            $('button', $form).prop('disabled', true)
                             .html('Processing payment...');
                        
            Stripe.setPublishableKey(app.options.stripe_key);
            Stripe.card.createToken({
                number: number,
                cvc: ccv,
                exp_month: month,
                exp_year: year
            }, function(status, response) {
                NProgress.done();
                if (response.error) {
                    app.notify(response.error.message);
                }
                else {
                    var form_data = 'token='+response.id;
                    $.post('/api/upgrade', form_data, function(data){
                        if (data.error) {
                            NProgress.done();
                            $('button', $form).html('Pay Securely')
                                              .prop('disabled', false);
                            
                            app.notify(data.error);
                            $('.billing-form button').prop('disabled', false);
                        }
                        else {
                            // Paid \o/
                            app.options.paid = 1;
                            // Remove payment form ish
                            $('.upgrade-btn-wrp, .upgrade-form').remove();
                            $('.pay-div').html('<p>Your plan expires '+data.exp_date+'. <br><a href="#" class="button primary cancel-renewal-btn" style="font-size:90%">Cancel auto-renewal</a></p>');
                        }

                    }, 'json');
                }
                $('button', $form).html('Pay Securely')
                                  .prop('disabled', false);
            });
            
            return false;
        },
		cancelRenewal: function(){
            // Assume done
            $('.cancel-renewal-btn').prev().remove();
            $('.cancel-renewal-btn').remove();
            $('.pay-div p').append('Autorenewal has been canceled.');
            $('.stripe-powered').prev().remove();
            $('.stripe-powered').remove();
            // Then do
            $.get('/api/cancel-renewal');
            
            return false;
        },
		//*/
        modalClose: function(){
            $('.tz-overlay').hide();
            return false;
        },
		submit: function(e){
			var $this = $(e.target);
			var clickElement = $this.html();
            //console.log(clickElement);
            var postUrl = app.options.path+'/api/post';
			if (clickElement == actionButtons.save) {
				$('input[name=action]').val('draft');
				$('.button-grp li button').eq(0).html(actionButtons.save);
				$('.button-grp li button').eq(1).html(actionButtons.preview);
				$('.button-grp li button').eq(2).html(actionButtons.post);
				$('.button-grp li button').eq(3).html(actionButtons.later);
			}
			else if (clickElement == actionButtons.preview) {
                postUrl = app.options.path+'/api/preview';
				$('input[name=action]').val('preview');
				$('.button-grp li button').eq(0).html(actionButtons.preview);
				$('.button-grp li button').eq(1).html(actionButtons.post);
				$('.button-grp li button').eq(2).html(actionButtons.later);
				$('.button-grp li button').eq(3).html(actionButtons.save);
			}
			else if (clickElement == actionButtons.later) {
                this.$('.button-grp').removeClass('opened');
				$('input[name=action]').val('schedule');
                $('.tz-overlay').show();
                return false;
			}
			else {
				$('input[name=action]').val('post');
				$('.button-grp li button').eq(0).html(actionButtons.post);
				$('.button-grp li button').eq(1).html(actionButtons.preview);
				$('.button-grp li button').eq(2).html(actionButtons.later);
				$('.button-grp li button').eq(3).html(actionButtons.save);
			}
            
            this._submit(postUrl, $('.post-form').serialize(), clickElement);
            
			return false;
		},        
        schedulePost: function(){
            var v = $('.tz-overlay input[name=at]').val();
            if (v == '') {
                app.notify("You did not enter a schedule time");
                $('.tz-overlay input[name=at]').focus();
                return false;
            }
            
            var formData = $('.post-form').serialize()+'&at='+v;
            this._submit(app.options.path+'/api/schedule', formData, actionButtons.later);
            return false;            
        },
        _submit: function(postUrl, formData, clickElement){
			NProgress.start();
            var draftId = $('input[name=save_id]').val();
			var that = this;
			this.$('.button-grp').addClass('disabled');
			this.$('.button-grp button').prop('disabled', true);
			$.post(postUrl, formData, function(data){
				NProgress.done();
				$('.button-grp').removeClass('disabled');
				$('.button-grp button').prop('disabled', false);
				if (data.preview) {
                    var pageWidth = $(window).width();
                    if ($('.textarea').hasClass('wrapper')) {
                        $('.textarea').removeClass('wrapper');
                        var textareaWidth = $('textarea').width();
                        textareaWidth = Math.round(pageWidth/2) - 10;
                        $('textarea').css({maxWidth:textareaWidth+'px',width:textareaWidth+'px',height:'480px',float:'left'});
                        $('.preview-pane').show().css({width:textareaWidth+'px',height:'480px'}).html(data.content);
                    }
                    else {
                        $('.preview-pane').html(data.content);
                    }
                }
				else if (data.done) {
                    // Hide preview
                    if (!$('.textarea').hasClass('wrapper')) {
                        $('.textarea').addClass('wrapper');
                        $('textarea').css({maxWidth:'',float:'',width:'100%'});
                        $('.preview-pane').hide().html('');
                    }
                    // Delete draft
                    //console.log(draftId);
                    var draftModel = window.app.Drafts.get(draftId);
                    if (!_.isUndefined(draftModel))
                        draftModel.trigger('silent_delete');
					// clear
					$(".post-form")[0].reset();
					$("input[type=hidden]").val('');
                    // reset button
					if (clickElement == actionButtons.save) {
                        $('.button-grp li button').eq(0).html(actionButtons.post);
                        $('.button-grp li button').eq(1).html(actionButtons.preview);
                        $('.button-grp li button').eq(2).html(actionButtons.later);
                        $('.button-grp li button').eq(3).html(actionButtons.save);
					}
                    else if (clickElement == actionButtons.later) {
                        $('.tz-overlay form')[0].reset();
                        $('.tz-overlay').hide();
                    }
                    
					// back wt status message
                    that.redirPostView();
                    var msg = data.schedule ? 'Post scheduled for '+data.date : 'Post committed. It should be available shortly.';
                    app.Posts.fetch({reset:true, data: {page: app.options.page, start: 0}});
                    app.notify(msg, true);
				}
				else {
					// error
					app.notify(data.error);
				}
			}, 'json');
			this.$('.button-grp').removeClass('opened');
        }
	});

	Backbone.history.start({pushState: true});
});
