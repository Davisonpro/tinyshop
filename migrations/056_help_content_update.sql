-- 056: Update help articles and add new FAQ content
-- Adds Troubleshooting category, updates custom domain article,
-- adds common questions sellers actually ask.

-- 1. Add Troubleshooting category
INSERT INTO help_categories (name, slug, icon, description, sort_order)
VALUES ('Troubleshooting', 'troubleshooting', 'fa-triangle-exclamation', 'Common issues and how to fix them', 14);

SET @troubleshooting_id = LAST_INSERT_ID();

-- 2. Update "Connecting a custom domain" article with much more detail
UPDATE help_articles
SET summary = 'Connect your own domain name to your shop for a professional address.',
    content = '<p>Instead of the default shop link, you can connect your own domain (like <strong>myshop.com</strong>) so customers see your brand name in the address bar.</p>

<h3>What you need</h3>
<ul>
<li>A registered domain name from any provider (Namecheap, GoDaddy, Hostinger, Cloudflare, etc.)</li>
<li>A paid plan that includes custom domain support</li>
</ul>

<h3>Important: Use a main domain, not a subdomain</h3>
<p>You need to connect a <strong>main domain</strong> like <strong>myshop.com</strong> — not a subdomain like <strong>shop.mysite.com</strong>.</p>
<p>If you only have a subdomain, you''ll need to either:</p>
<ul>
<li>Buy your own domain name (they start at around $10/year), or</li>
<li>Use the free shop link we give you instead</li>
</ul>

<h3>How to connect your domain</h3>
<p><strong>Step 1:</strong> Go to <strong>Dashboard → Shop</strong> and tap <strong>Custom Domain</strong>.</p>
<p><strong>Step 2:</strong> Enter your domain name (e.g. <strong>myshop.com</strong>).</p>
<p><strong>Step 3:</strong> Go to your domain provider''s website and change your <strong>nameservers</strong> to the ones we show you. This tells the internet to point your domain to us.</p>
<p><strong>Step 4:</strong> Come back and tap <strong>Connect</strong>.</p>

<h3>How to change nameservers</h3>
<p>Every domain provider has a slightly different process, but the general steps are:</p>
<ol>
<li>Log in to your domain provider (where you bought the domain)</li>
<li>Find your domain''s settings — look for "Nameservers" or "DNS"</li>
<li>Change from the default nameservers to the custom ones we show you</li>
<li>Save the changes</li>
</ol>
<p>If you''re not sure how, search for "<em>change nameservers [your provider name]</em>" — most providers have a help guide for this.</p>

<h3>After connecting</h3>
<ul>
<li><strong>SSL certificate:</strong> We automatically set up HTTPS (the padlock icon) for your domain. This usually happens within a few minutes.</li>
<li><strong>www redirect:</strong> If someone visits <strong>www.myshop.com</strong>, they''ll be automatically redirected to <strong>myshop.com</strong>.</li>
<li><strong>Your old shop link still works:</strong> The default link we gave you keeps working too — it just redirects to your custom domain.</li>
</ul>

<h3>Common issues</h3>
<p><strong>"Site can''t be reached" after connecting:</strong> Nameserver changes can take up to 24–48 hours to fully take effect, though it''s usually much faster. Try again in a few hours.</p>
<p><strong>Site shows "Not Secure":</strong> The SSL certificate is still being set up. Wait about 10–15 minutes and refresh. If it persists after an hour, disconnect and reconnect the domain.</p>
<p><strong>"This domain is already in use":</strong> Another shop on our platform is using this domain. If you own the domain and didn''t set this up, contact our support.</p>',
    keywords = 'custom domain, own domain, connect domain, nameservers, DNS, SSL, HTTPS, www, subdomain'
WHERE slug = 'connecting-a-custom-domain';

-- 3. Update "Sharing your shop link" to mention custom domains
UPDATE help_articles
SET content = '<p>Every shop gets a unique link that you can share with customers.</p>

<h3>Finding your link</h3>
<p>Go to <strong>Dashboard → Shop</strong>. Your shop link is displayed near the top of the page. Tap the copy button to copy it to your clipboard.</p>

<h3>If you have a custom domain</h3>
<p>If you''ve connected your own domain, share that instead — it looks more professional. Your default link will still work and automatically redirect visitors to your custom domain.</p>

<h3>Where to share it</h3>
<ul>
<li>Instagram, TikTok, and Twitter bios</li>
<li>WhatsApp messages and status updates</li>
<li>Facebook posts and stories</li>
<li>Email signatures</li>
<li>Business cards and flyers</li>
<li>Product packaging</li>
</ul>

<p><strong>Tip:</strong> Pin your shop link to the top of your social media profiles. The easier it is for people to find, the more visitors your shop will get.</p>',
    keywords = 'shop link, share, URL, custom domain, social media, WhatsApp, Instagram'
WHERE slug = 'sharing-your-shop-link';

-- 4. Troubleshooting articles

-- 4a. My site shows "Not Secure" or no padlock
INSERT INTO help_articles (category_id, title, slug, summary, content, keywords, sort_order, is_published)
VALUES (@troubleshooting_id, 'My site shows "Not Secure"', 'site-not-secure', 'What to do if your shop shows a security warning in the browser.',
'<p>If you see <strong>"Not Secure"</strong> next to your domain in the browser address bar, it means the SSL certificate (the thing that gives you the padlock icon and HTTPS) hasn''t been set up yet.</p>

<h3>If you just connected your domain</h3>
<p>Don''t worry — this is normal. After you connect a custom domain, we automatically request an SSL certificate for you. This can take <strong>5 to 15 minutes</strong>.</p>
<p>Just wait a bit and then refresh the page. The padlock should appear.</p>

<h3>If it''s been more than an hour</h3>
<p>Try these steps:</p>
<ol>
<li>Go to <strong>Dashboard → Shop → Custom Domain</strong></li>
<li>Remove your domain</li>
<li>Wait about 30 seconds</li>
<li>Add the domain again</li>
</ol>
<p>This re-triggers the SSL setup process. Your shop won''t be affected — just refresh after a few minutes.</p>

<h3>Why does this happen?</h3>
<p>SSL certificates are issued by a service called Let''s Encrypt. Sometimes there''s a short delay while they verify your domain. It''s completely automatic — you don''t need to do anything technical.</p>',
'not secure, SSL, HTTPS, padlock, certificate, security warning', 1, 1);

-- 4b. My site isn't loading / can't be reached
INSERT INTO help_articles (category_id, title, slug, summary, content, keywords, sort_order, is_published)
VALUES (@troubleshooting_id, 'My site isn''t loading', 'site-not-loading', 'What to do when your shop shows "can''t be reached" or doesn''t load.',
'<p>If your shop isn''t loading or you see a message like <strong>"This site can''t be reached"</strong>, here are the most common reasons and fixes.</p>

<h3>If you''re using a custom domain</h3>

<p><strong>Just connected it?</strong> Nameserver changes can take up to 24–48 hours to spread across the internet (called "DNS propagation"). It''s usually much faster, but sometimes it takes a while. Try again in a few hours.</p>

<p><strong>Was working before?</strong> Check if your nameservers are still pointing to us. Log in to your domain provider and make sure the nameservers haven''t changed. Sometimes providers reset them after renewals.</p>

<h3>If you''re using the default shop link</h3>
<p>The default links (yourname.myduka.link) should always work. If it''s not loading:</p>
<ul>
<li>Try a different browser or device</li>
<li>Try turning off your VPN if you''re using one</li>
<li>Clear your browser cache</li>
</ul>

<h3>Check from your phone</h3>
<p>Try opening your shop on your phone using mobile data (not WiFi). If it loads on mobile data but not on WiFi, the issue is with your internet connection or router, not your shop.</p>

<h3>Still not working?</h3>
<p>Contact our support team and we''ll look into it right away.</p>',
'site not loading, can''t be reached, DNS, not working, error, down', 2, 1);

-- 4c. I see someone else's site / wrong site
INSERT INTO help_articles (category_id, title, slug, summary, content, keywords, sort_order, is_published)
VALUES (@troubleshooting_id, 'I see the wrong site on my domain', 'wrong-site-on-domain', 'What to do if your domain shows a different website instead of your shop.',
'<p>If you visit your custom domain and see a different website (like a "coming soon" page, or your old hosting), it means your domain isn''t fully pointed to us yet.</p>

<h3>Check your nameservers</h3>
<p>Go to your domain provider and make sure you''ve changed the nameservers to the ones we provided. If you only changed the A record or added a redirect, that won''t work — you need to change the actual <strong>nameservers</strong>.</p>

<h3>Wait for changes to take effect</h3>
<p>After changing nameservers, it can take up to 24–48 hours for the change to fully work everywhere. During this time, you might sometimes see your old site and sometimes see your shop — this is normal.</p>

<h3>Check for Cloudflare or other proxies</h3>
<p>If you''re using Cloudflare or a similar service, you need to either:</p>
<ul>
<li>Turn off the proxy (set DNS to "DNS only" in Cloudflare), or</li>
<li>Change your nameservers away from Cloudflare to the ones we gave you</li>
</ul>',
'wrong site, old site, coming soon, nameservers, DNS, Cloudflare, proxy', 3, 1);

-- 4d. Products not showing / empty shop
INSERT INTO help_articles (category_id, title, slug, summary, content, keywords, sort_order, is_published)
VALUES (@troubleshooting_id, 'My shop looks empty or products aren''t showing', 'products-not-showing', 'Why your shop might look empty and how to fix it.',
'<p>If you visit your shop and don''t see your products, here are the common reasons:</p>

<h3>Products might be hidden</h3>
<p>When you create a product, it''s visible by default. But if you set any products to hidden, they won''t appear on your shop page. Go to <strong>Dashboard → Products</strong> and check if your products are marked as visible.</p>

<h3>No products added yet</h3>
<p>If you''re just starting out, you need to add at least one product before your shop shows anything. Go to <strong>Dashboard → Products</strong> and tap the <strong>+</strong> button to add your first product.</p>

<h3>Checking from the right link</h3>
<p>Make sure you''re visiting the right link. Go to your <strong>Dashboard → Shop</strong> to see your actual shop URL and try opening it from there.</p>',
'empty shop, products not showing, hidden products, no products, blank shop', 4, 1);

-- 4e. Can't log in
INSERT INTO help_articles (category_id, title, slug, summary, content, keywords, sort_order, is_published)
VALUES (@troubleshooting_id, 'I can''t log in to my account', 'cant-log-in', 'Trouble signing in? Here''s how to get back into your account.',
'<p>If you''re having trouble signing in, try these steps:</p>

<h3>Forgot your password?</h3>
<p>Tap <strong>"Forgot password?"</strong> on the login page. We''ll send you an email with a link to reset your password. Check your spam folder if you don''t see it.</p>

<h3>Signed up with Google?</h3>
<p>If you created your account using "Sign in with Google", you can''t use a regular password. Tap the <strong>"Continue with Google"</strong> button on the login page instead.</p>

<h3>Email not recognized?</h3>
<p>Make sure you''re using the same email you signed up with. If you have multiple email addresses, try the others.</p>

<h3>Still can''t get in?</h3>
<p>Contact our support team with the email address you used to sign up, and we''ll help you recover your account.</p>',
'login, sign in, password, forgot password, can''t login, locked out, Google', 5, 1);

-- 5. Additional articles in existing categories

-- 5a. Getting Started: What is MyDuka?
INSERT INTO help_articles (category_id, title, slug, summary, content, keywords, sort_order, is_published)
VALUES (1, 'What is MyDuka?', 'what-is-myduka', 'A quick overview of what MyDuka is and how it works.',
'<p><strong>MyDuka</strong> is a platform that lets you create your own online shop in minutes — no coding or technical skills needed.</p>

<h3>How it works</h3>
<ol>
<li><strong>Sign up</strong> — Create a free account</li>
<li><strong>Add products</strong> — Upload photos, set prices, write descriptions</li>
<li><strong>Share your link</strong> — Send your shop link to customers via WhatsApp, social media, or anywhere</li>
<li><strong>Get paid</strong> — Customers place orders and pay through M-Pesa, Stripe, PayPal, or cash on delivery</li>
</ol>

<h3>Who is it for?</h3>
<p>MyDuka is built for anyone who wants to sell things online — whether you''re selling clothes, electronics, food, art, or services. You don''t need any technical knowledge.</p>

<h3>Is it free?</h3>
<p>You can start with a free plan that includes everything you need to get going. Paid plans unlock extra features like custom domains, more products, and analytics.</p>',
'what is myduka, about, overview, how it works, getting started, free', 0, 1);

-- 5b. Products: Product photos tips
INSERT INTO help_articles (category_id, title, slug, summary, content, keywords, sort_order, is_published)
VALUES (2, 'Taking great product photos', 'product-photo-tips', 'Simple tips to make your products look their best.',
'<p>Good photos are the single biggest factor in getting sales. You don''t need a professional camera — a smartphone works perfectly.</p>

<h3>Basic tips</h3>
<ul>
<li><strong>Use natural light.</strong> Take photos near a window during the day. Avoid harsh flash.</li>
<li><strong>Use a clean background.</strong> A plain white or light surface works best. A bedsheet or large piece of paper can work.</li>
<li><strong>Show the product clearly.</strong> Fill most of the frame with the product. Customers want to see what they''re buying.</li>
<li><strong>Take multiple angles.</strong> Front, back, side, and close-ups of important details.</li>
</ul>

<h3>For clothing</h3>
<ul>
<li>Lay items flat on a clean surface, or hang them up</li>
<li>If possible, show someone wearing the item</li>
<li>Show color accurately — avoid filters that change colors</li>
</ul>

<h3>Photo size</h3>
<p>Photos should be at least <strong>800 × 800 pixels</strong> for best quality. Most phone cameras shoot much larger than this, so you''re usually fine.</p>

<p><strong>Tip:</strong> Square photos (same width and height) look best in the product grid.</p>',
'photos, images, pictures, product photos, tips, quality, camera', 4, 1);

-- 5c. Orders: What do order statuses mean?
INSERT INTO help_articles (category_id, title, slug, summary, content, keywords, sort_order, is_published)
VALUES (4, 'What do order statuses mean?', 'order-statuses-explained', 'A quick guide to each order status and when to use them.',
'<p>Each order in your dashboard has a status that tells you and your customer where the order is at.</p>

<h3>Status meanings</h3>
<ul>
<li><strong>Pending</strong> — The order was just placed. Payment may or may not be confirmed yet.</li>
<li><strong>Confirmed</strong> — You''ve seen the order and confirmed it. Payment has been received (or COD was selected).</li>
<li><strong>Shipped</strong> — The order is on its way to the customer.</li>
<li><strong>Delivered</strong> — The customer has received the order.</li>
<li><strong>Cancelled</strong> — The order was cancelled (by you or the customer).</li>
</ul>

<h3>Tips</h3>
<p>Update the status as soon as something changes — your customers can see the status on their order page, so keeping it current builds trust.</p>',
'order status, pending, confirmed, shipped, delivered, cancelled, tracking', 2, 1);

-- 5d. Payments: My customer paid but order shows pending
INSERT INTO help_articles (category_id, title, slug, summary, content, keywords, sort_order, is_published)
VALUES (5, 'Customer paid but order shows pending', 'payment-received-order-pending', 'Why an order might still show as pending even after payment.',
'<p>Sometimes a customer tells you they''ve paid, but the order still shows as "Pending" in your dashboard. Here''s why this can happen:</p>

<h3>For M-Pesa payments</h3>
<p>M-Pesa confirmations are usually instant, but occasionally there''s a delay of a few minutes. If the order is still pending after 10 minutes, the payment may not have gone through. Ask the customer to check their M-Pesa message for a confirmation.</p>

<h3>For Stripe / card payments</h3>
<p>Card payments are confirmed immediately. If the order is still pending, the payment likely failed. The customer should check with their bank or try a different card.</p>

<h3>For Cash on Delivery</h3>
<p>COD orders always start as "Pending" since no payment happens upfront. You should confirm the order manually once you verify the customer wants to proceed.</p>

<h3>What to do</h3>
<p>If you''ve confirmed with the customer that they paid, you can manually change the order status to <strong>Confirmed</strong> from your dashboard.</p>',
'payment pending, paid but pending, M-Pesa, order not confirmed, manual confirm', 3, 1);

-- 5e. Shop Design: What are color palettes?
INSERT INTO help_articles (category_id, title, slug, summary, content, keywords, sort_order, is_published)
VALUES (6, 'What are color palettes?', 'color-palettes-explained', 'How to change the colors of your shop to match your brand.',
'<p>Color palettes let you change the look and feel of your shop without any design skills.</p>

<h3>How to change your palette</h3>
<p>Go to <strong>Dashboard → Shop → Design</strong> and tap on a color palette to preview it. The change is applied instantly to your shop.</p>

<h3>Available palettes</h3>
<p>We offer several pre-designed palettes — from clean and minimal to bold and vibrant. Each palette changes the accent colors, button colors, and overall mood of your shop.</p>

<h3>Which one should I use?</h3>
<p>Choose something that matches your brand. If you sell luxury items, a minimal palette might work best. If you sell fun, colorful products, go for something more vibrant. You can change it anytime, so feel free to experiment.</p>',
'color palette, colors, theme colors, design, brand, customization', 3, 1);

-- 5f. Shop Settings: How do I change my shop name?
INSERT INTO help_articles (category_id, title, slug, summary, content, keywords, sort_order, is_published)
VALUES (7, 'How do I change my shop name?', 'change-shop-name', 'How to update your shop name after creating your account.',
'<p>You can change your shop name at any time.</p>

<h3>Steps</h3>
<ol>
<li>Go to <strong>Dashboard → Shop</strong></li>
<li>Tap on your shop name at the top</li>
<li>Type the new name</li>
<li>Tap <strong>Save</strong></li>
</ol>

<p>The new name will appear on your shop page immediately. Your shop link (URL) stays the same — changing your shop name doesn''t change your link.</p>',
'shop name, store name, change name, rename, update name', 3, 1);

-- 5g. Account: How do I contact support?
INSERT INTO help_articles (category_id, title, slug, summary, content, keywords, sort_order, is_published)
VALUES (12, 'How do I contact support?', 'contact-support', 'How to reach us if you need help.',
'<p>We''re here to help! Here''s how to reach us:</p>

<h3>Email</h3>
<p>Send us an email at <strong>support@myduka.link</strong>. We typically respond within 24 hours.</p>

<h3>Tips for faster help</h3>
<ul>
<li>Include your shop name or link so we can find your account</li>
<li>If something isn''t working, describe what you see — screenshots help a lot</li>
<li>Let us know what device you''re using (phone, computer) and which browser</li>
</ul>',
'support, help, contact, email, customer service', 4, 1);

-- 5h. For Customers: Is it safe to buy from a MyDuka shop?
INSERT INTO help_articles (category_id, title, slug, summary, content, keywords, sort_order, is_published)
VALUES (13, 'Is it safe to buy from a MyDuka shop?', 'is-it-safe-to-buy', 'Information about payment security and buyer safety.',
'<p>Yes! All payments on MyDuka shops are processed through trusted, secure payment providers.</p>

<h3>Payment security</h3>
<ul>
<li><strong>Card payments</strong> are processed through <strong>Stripe</strong>, one of the world''s largest payment companies. Your card details are never stored by the shop owner.</li>
<li><strong>PayPal</strong> payments go through PayPal''s secure checkout. The shop owner never sees your PayPal password or financial details.</li>
<li><strong>M-Pesa</strong> payments go through Safaricom''s official API.</li>
</ul>

<h3>HTTPS encryption</h3>
<p>All MyDuka shops use HTTPS encryption (the padlock in your browser), which means your connection is secure and private.</p>

<h3>Concerns about a specific shop?</h3>
<p>While we provide the platform, each shop is run independently by its owner. If you have concerns about a specific order or product, contact the shop owner directly using the contact details on their shop page.</p>',
'safe, secure, trust, security, payment, privacy, HTTPS, SSL', 4, 1);

-- 5i. Billing: Is MyDuka free?
INSERT INTO help_articles (category_id, title, slug, summary, content, keywords, sort_order, is_published)
VALUES (11, 'Is MyDuka free?', 'is-myduka-free', 'What you get for free and what paid plans offer.',
'<p>Yes, you can use MyDuka completely free to create and run your shop.</p>

<h3>What''s included for free</h3>
<ul>
<li>Your own shop page with a unique link</li>
<li>Add products with photos and descriptions</li>
<li>Accept orders from customers</li>
<li>Accept payments via M-Pesa, Stripe, PayPal, or cash on delivery</li>
<li>Choose a shop theme</li>
</ul>

<h3>What paid plans add</h3>
<p>Paid plans unlock extras like:</p>
<ul>
<li>Connect your own custom domain (e.g. myshop.com)</li>
<li>More product listings</li>
<li>Shop analytics and visitor stats</li>
<li>Premium themes</li>
<li>Priority support</li>
</ul>

<p>You can upgrade at any time from <strong>Dashboard → Billing</strong>. There''s no commitment — you can cancel anytime.</p>',
'free, pricing, cost, paid, plan, subscription, upgrade', 3, 1);
