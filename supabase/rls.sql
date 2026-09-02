CREATE OR REPLACE FUNCTION public.is_admin()
RETURNS BOOLEAN
LANGUAGE SQL
STABLE
AS $$
  SELECT EXISTS (
    SELECT 1
    FROM public.users u
    WHERE u.id = auth.uid()
      AND u.role = 'admin'
  );
$$;

ALTER TABLE public.users ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.products ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.product_images ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.product_sizes ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.product_colors ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.product_specs ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.cart ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.orders ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.order_items ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.wishlist ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.virtual_cards ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.card_transactions ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.promo_codes ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.contact_messages ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.login_attempts ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.remember_tokens ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Users can view their own profile"
ON public.users FOR SELECT
USING (auth.uid() = id OR public.is_admin());

CREATE POLICY "Users can update their own profile"
ON public.users FOR UPDATE
USING (auth.uid() = id)
WITH CHECK (auth.uid() = id);

CREATE POLICY "Admins can manage all profiles"
ON public.users FOR ALL
USING (public.is_admin())
WITH CHECK (public.is_admin());

CREATE POLICY "Anyone can view active products"
ON public.products FOR SELECT
USING (is_active = TRUE OR public.is_admin());

CREATE POLICY "Admins can manage products"
ON public.products FOR ALL
USING (public.is_admin())
WITH CHECK (public.is_admin());

CREATE POLICY "Users can read product-related data"
ON public.product_images FOR SELECT
USING (TRUE);

CREATE POLICY "Users can read product size/color/spec data"
ON public.product_sizes FOR SELECT USING (TRUE);
CREATE POLICY "Users can read product size/color/spec data"
ON public.product_colors FOR SELECT USING (TRUE);
CREATE POLICY "Users can read product size/color/spec data"
ON public.product_specs FOR SELECT USING (TRUE);

CREATE POLICY "Admins can manage product metadata"
ON public.product_images FOR ALL USING (public.is_admin()) WITH CHECK (public.is_admin());
CREATE POLICY "Admins can manage product sizes"
ON public.product_sizes FOR ALL USING (public.is_admin()) WITH CHECK (public.is_admin());
CREATE POLICY "Admins can manage product colors"
ON public.product_colors FOR ALL USING (public.is_admin()) WITH CHECK (public.is_admin());
CREATE POLICY "Admins can manage product specs"
ON public.product_specs FOR ALL USING (public.is_admin()) WITH CHECK (public.is_admin());

CREATE POLICY "Users can read their cart"
ON public.cart FOR SELECT
USING (user_id = auth.uid() OR (session_id IS NOT NULL AND auth.uid() IS NULL));

CREATE POLICY "Users can manage their cart"
ON public.cart FOR INSERT
WITH CHECK (user_id = auth.uid() OR auth.uid() IS NULL);

CREATE POLICY "Users can update their own cart"
ON public.cart FOR UPDATE
USING (user_id = auth.uid())
WITH CHECK (user_id = auth.uid());

CREATE POLICY "Users can remove their own cart items"
ON public.cart FOR DELETE
USING (user_id = auth.uid());

CREATE POLICY "Users can read own orders"
ON public.orders FOR SELECT
USING (user_id = auth.uid() OR public.is_admin());

CREATE POLICY "Users can create their own orders"
ON public.orders FOR INSERT
WITH CHECK (user_id = auth.uid());

CREATE POLICY "Admins can manage orders"
ON public.orders FOR UPDATE USING (public.is_admin()) WITH CHECK (public.is_admin());

CREATE POLICY "Users can read own order items"
ON public.order_items FOR SELECT
USING (
  EXISTS (
    SELECT 1 FROM public.orders o
    WHERE o.id = order_id
      AND (o.user_id = auth.uid() OR public.is_admin())
  )
);

CREATE POLICY "Users can insert their own order items"
ON public.order_items FOR INSERT
WITH CHECK (
  EXISTS (
    SELECT 1 FROM public.orders o
    WHERE o.id = order_id
      AND o.user_id = auth.uid()
  )
);

CREATE POLICY "Users can read their wishlist"
ON public.wishlist FOR SELECT USING (user_id = auth.uid());
CREATE POLICY "Users can manage their wishlist"
ON public.wishlist FOR ALL
USING (user_id = auth.uid())
WITH CHECK (user_id = auth.uid());

CREATE POLICY "Users can read own virtual cards"
ON public.virtual_cards FOR SELECT
USING (user_id = auth.uid() OR public.is_admin());

CREATE POLICY "Users can update own virtual cards"
ON public.virtual_cards FOR UPDATE
USING (user_id = auth.uid())
WITH CHECK (user_id = auth.uid());

CREATE POLICY "Users can read own card transactions"
ON public.card_transactions FOR SELECT
USING (
  EXISTS (
    SELECT 1 FROM public.virtual_cards vc
    WHERE vc.id = card_id
      AND (vc.user_id = auth.uid() OR public.is_admin())
  )
);

CREATE POLICY "Users can insert own card transactions"
ON public.card_transactions FOR INSERT
WITH CHECK (
  EXISTS (
    SELECT 1 FROM public.virtual_cards vc
    WHERE vc.id = card_id
      AND vc.user_id = auth.uid()
  )
);

CREATE POLICY "Anyone can read active promo codes"
ON public.promo_codes FOR SELECT USING (is_active = TRUE);
CREATE POLICY "Admins can manage promo codes"
ON public.promo_codes FOR ALL USING (public.is_admin()) WITH CHECK (public.is_admin());

CREATE POLICY "Anyone can create contact messages"
ON public.contact_messages FOR INSERT WITH CHECK (TRUE);
CREATE POLICY "Admins can read and update contact messages"
ON public.contact_messages FOR SELECT USING (public.is_admin());
CREATE POLICY "Admins can update contact messages"
ON public.contact_messages FOR UPDATE USING (public.is_admin()) WITH CHECK (public.is_admin());

CREATE POLICY "System can insert login attempts"
ON public.login_attempts FOR INSERT WITH CHECK (TRUE);
CREATE POLICY "Admins can read login attempts"
ON public.login_attempts FOR SELECT USING (public.is_admin());

CREATE POLICY "Users can read their remember tokens"
ON public.remember_tokens FOR SELECT USING (user_id = auth.uid());
CREATE POLICY "Users can insert their remember tokens"
ON public.remember_tokens FOR INSERT WITH CHECK (user_id = auth.uid());
CREATE POLICY "Users can delete their remember tokens"
ON public.remember_tokens FOR DELETE USING (user_id = auth.uid());
