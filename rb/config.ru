ENV['GEM_HOME'] = '/usr/local/rvm/gems/ruby-2.1.2'

require 'liquid'

class HighlightBlock < Liquid::Block
  include Liquid::StandardFilters

  SYNTAX = /^([a-zA-Z0-9.+#-]+)((\s+\w+(=(\w+|"([0-9]+\s)*[0-9]+"))?)*)$/

  def initialize(tag_name, markup, tokens)
    super
    if markup.strip =~ SYNTAX
      @lang = $1.downcase
      @options = {}
      if defined?($2) && $2 != ''
        # Split along 3 possible forms -- key="<quoted list>", key=value, or key
        $2.scan(/(?:\w="[^"]*"|\w=\w|\w)+/) do |opt|
          key, value = opt.split('=')
          # If a quoted list, convert to array
          if value && value.include?("\"")
              value.gsub!(/"/, "")
              value = value.split
          end
          @options[key.to_sym] = value || true
        end
      end
      @options[:linenos] = "inline" if @options.key?(:linenos) and @options[:linenos] == true
    else
      # Syntax error
    end
  end

  def render(context)
    prefix = context["highlighter_prefix"] || ""
    suffix = context["highlighter_suffix"] || ""
    code = super.to_s.strip

    output = render_codehighlighter(code)
    rendered_output = add_code_tag(output)
    prefix + rendered_output + suffix
  end

  def render_codehighlighter(code)
    "<div class=\"highlight\"><pre>#{h(code).strip}</pre></div>"
  end

  def add_code_tag(code)
    # Add nested <code> tags to code blocks
    code = code.sub(/<pre>\n*/,'<pre><code class="language-' + @lang.to_s.gsub("+", "-") + '" data-lang="' + @lang.to_s + '">')
    code = code.sub(/\n*<\/pre>/,"</code></pre>")
    code.strip
  end

end

Liquid::Template.register_tag('highlight', HighlightBlock)

class MarkdownPreview
  def call(env)
    req = Rack::Request.new(env)
    
    # Get server args
    parser = req.params['parser']
    markdown = req.params['markdown']
    
    # Render highlight liquid tags
    template = Liquid::Template.parse(markdown)
    markdown = template.render
    
    # Convert to html
    Rack::Response.new.finish do |res|
      res['Content-Type'] = 'text/html'
      res.status = 200
      str = ""
      case parser
        when 'redcarpet'
            require 'redcarpet'
            str = Redcarpet::Markdown.new(Redcarpet::Render::HTML).render(markdown)
        when 'rdiscount'
            require 'rdiscount'
            str = RDiscount.new(markdown).to_html
        when 'maruku'
            require 'maruku'
            str = Maruku.new(markdown).to_html
        else 'kramdown'
            require 'kramdown'
            str = Kramdown::Document.new(markdown).to_html
      end
      res.write str
    end
  end
end

run MarkdownPreview.new