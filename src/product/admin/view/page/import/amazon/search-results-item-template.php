<script id="aff-amazon-import-search-results-item-template" type="text/template">
    <div class="aff-import-search-results-item aff-panel " data-parent="<% if(typeof variants !== 'undefined' && variants !== null) { %>true<% } else { %>false<% } %>" <% if(typeof shops !== 'undefined' && shops !== null) { %>data-affiliate-product-id="<%= shops[0].tracking.affiliate_product_id %>"<% } %>>
        <div class="aff-import-search-results-item-content aff-panel-body">
            <div class="aff-import-search-results-item-content-media">
                <% if(typeof thumbnail !== 'undefined' && thumbnail !== null) { %>
                    <div class="aff-import-search-results-item-thumbnail">
                        <img class="aff-import-search-results-item-thumbnail-image" src="<%= thumbnail.src %>">
                    </div>
                <% } %>
            </div>

            <div class="aff-import-search-results-item-content-main">
                <h1 class="aff-import-search-results-item-title">
                    <%= name %>
                    <% if(typeof shops !== 'undefined' && shops !== null && shops[0].tracking.affiliate_link !== null) { %>
                        <a class="aff-import-search-results-item-affiliate-link dashicons dashicons-admin-links" href="<%= shops[0].tracking.affiliate_link %>" target="_blank"></a>
                    <% } %>
                </h1>

                <% if(typeof shops !== 'undefined' && shops !== null && shops[0].pricing.price !== null) { %>
                    <div class="aff-import-search-results-item-price">
                        <span class="aff-import-search-results-item-price-current">
                            <%= shops[0].pricing.price.value %> <%= shops[0].pricing.price.currency.symbol %>
                        </span>

                        <% if(shops[0].pricing.old_price) { %>
                            <span class="aff-import-search-results-item-price-old">
                                <%= shops[0].pricing.old_price.value %> <%= shops[0].pricing.old_price.currency.symbol %>
                            </span>
                        <% } %>
                    </div>
                <% } %>

                <% if(typeof shops !== 'undefined' && shops !== null && shops[0].tracking.affiliate_product_id !== null) { %>
                    <dl class="aff-import-search-results-item-other">
                        <dt><?php _e('ASIN', 'affilicious'); ?></dt>
                        <dd class="aff-import-search-results-item-affiliate-product-id"><%= shops[0].tracking.affiliate_product_id %></dd>
                    </dl>
                <% } %>

                <% if(typeof variants !== 'undefined' && variants !== null) { %>
                    <div class="aff-import-search-results-item-variants">
                        <h3 class="aff-import-search-results-item-variants-title"><?php _e('Variants', 'affilicious'); ?></h3>

                        <% _.each(variants, function(variant, i) { %>
                            <% if(i == 3) { %>
                                <a class="aff-import-search-results-item-variants-show-all" href="#"><?php _e('Show all', 'affilicious'); ?> (+<%= this.length - 3 %>)</a>
                            <% } %>

                            <div class="aff-import-search-results-item-variants-item" <% if(typeof shops !== 'undefined' && shops !== null) { %>data-affiliate-product-id="<%= variant.shops[0].tracking.affiliate_product_id %>"<% } %> <% if(i >= 3) { %>style="display: none;"<% } %>>
                                <h2 class="aff-import-search-results-item-variants-item-title"><%= variant.name %></h2>

                                <% if(typeof variant.shops !== 'undefined' && variant.shops !== null && variant.shops[0].pricing.price !== null) { %>
                                    <div class="aff-import-search-results-item-variants-item-price">
                                        <span class="aff-import-search-results-item-variants-item-price-current">
                                            <%= variant.shops[0].pricing.price.value %> <%= variant.shops[0].pricing.price.currency.symbol %>
                                        </span>

                                        <% if(variant.shops[0].pricing.old_price !== null) { %>
                                            <span class="aff-import-search-results-item-variants-item-price-old">
                                                <%= variant.shops[0].pricing.old_price.value %> <%= variant.shops[0].pricing.old_price.currency.symbol %>
                                            </span>
                                        <% } %>
                                    </div>
                                <% } %>

                                <% if(typeof variant.attributes !== 'undefined' && variant.attributes !== null) { %>
                                    <ul class="aff-import-search-results-item-variants-item-attributes">
                                        <% _.each(variant.attributes, function(attribute) { %>
                                            <li class="aff-import-search-results-item-variants-item-attributes-item">
                                                <span class="aff-import-search-results-item-variants-item-attributes-item-name"><%= attribute.name %></span>
                                                <span class="aff-import-search-results-item-variants-item-attributes-item-value"><%= attribute.value %></span>
                                            </li>
                                        <% }); %>
                                    </ul>
                                <% } %>
                            </div>
                        <% }, variants); %>
                    </div>
                <% } %>
            </div>
        </div>

        <div class="aff-import-search-results-item-actions aff-panel-footer ">
            <% if(!loading && !success && !error) { %>
                <button class="aff-import-search-results-item-actions-import"><?php _e('Import', 'affilicious'); ?></button>
            <% } else if(!loading && success) { %>
                <p class="aff-import-search-results-item-actions-done"><% if(successMessage) { %><%= successMessage %><% } else { %><?php _e('Successfully imported the product.', 'affilicious'); ?><% } %></p>
            <% } else if(!loading && error) { %>
                <p class="aff-import-search-results-item-actions-error"><% if(errorMessage) { %><%= errorMessage %><% } else { %><?php _e('Failed to import the product.', 'affilicious'); ?><% } %></p>
            <% } else { %>
                <div class="aff-import-search-results-item-actions-loading">
                    <span class="aff-import-search-results-item-actions-loading-spinner spinner is-active"></span>
                </div>
            <% } %>
        </div>
    </div>
</script>
