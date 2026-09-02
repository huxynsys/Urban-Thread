(function () {
  function getConfig() {
    return window.UT_CONFIG || {};
  }

  function getClient() {
    if (!window.supabase) {
      throw new Error('Supabase JS SDK is not loaded. Add the Supabase CDN script before this file.');
    }

    const { supabaseUrl, supabaseAnonKey } = getConfig();
    if (!supabaseUrl || !supabaseAnonKey || supabaseUrl.includes('YOUR_PROJECT_REF')) {
      throw new Error('Supabase config is missing or not configured. Update config.js with your project URL and anon key.');
    }

    return window.supabase.createClient(supabaseUrl, supabaseAnonKey, {
      auth: {
        persistSession: true,
        autoRefreshToken: true,
        detectSessionInUrl: true
      }
    });
  }

  async function ensureProfile(user, overrides = {}) {
    const supabase = getClient();
    const payload = {
      id: user.id,
      name: overrides.name || user.user_metadata?.full_name || user.email?.split('@')[0] || 'User',
      email: user.email,
      avatar: overrides.avatar || user.user_metadata?.avatar_url || null,
      google_id: overrides.google_id || user.user_metadata?.google_id || null,
      auth_provider: overrides.auth_provider || (user.app_metadata?.provider === 'google' ? 'google' : 'local'),
      email_verified: Boolean(user.email_confirmed_at || user.email_verified),
      updated_at: new Date().toISOString()
    };

    const { data, error } = await supabase.from('users').upsert(payload, { onConflict: 'id' }).select();
    if (error) {
      throw error;
    }

    return data && data[0] ? data[0] : payload;
  }

  async function signIn({ email, password }) {
    const supabase = getClient();
    const { data, error } = await supabase.auth.signInWithPassword({ email, password });
    if (error) {
      throw error;
    }

    if (data.user) {
      const profile = await ensureProfile(data.user);
      data.profile = profile;
    }

    return data;
  }

  async function signUp({ name, email, password }) {
    const supabase = getClient();
    const { data, error } = await supabase.auth.signUp({
      email,
      password,
      options: {
        emailRedirectTo: getConfig().authRedirectUrl || window.location.origin + '/reg.php',
        data: {
          full_name: name
        }
      }
    });

    if (error) {
      throw error;
    }

    if (data.user) {
      await ensureProfile(data.user, { name, email });
    }

    return data;
  }

  async function signOut() {
    const supabase = getClient();
    const { error } = await supabase.auth.signOut();
    if (error) {
      throw error;
    }
    return true;
  }

  async function getCurrentUser() {
    const supabase = getClient();
    const { data: authData, error: authError } = await supabase.auth.getUser();
    if (authError) {
      throw authError;
    }

    if (!authData?.user) {
      return { user: null, profile: null, session: null };
    }

    const { data: profileData, error: profileError } = await supabase
      .from('users')
      .select('*')
      .eq('id', authData.user.id)
      .maybeSingle();

    if (profileError && profileError.code !== 'PGRST116') {
      throw profileError;
    }

    return {
      user: authData.user,
      profile: profileData,
      session: authData.session || null
    };
  }

  async function getSessionState() {
    const supabase = getClient();
    const { data, error } = await supabase.auth.getSession();

    if (error) {
      throw error;
    }

    const user = data?.session?.user || null;
    if (!user) {
      return { logged_in: false, user: null, role: null };
    }

    const { data: profileData, error: profileError } = await supabase
      .from('users')
      .select('*')
      .eq('id', user.id)
      .maybeSingle();

    if (profileError && profileError.code !== 'PGRST116') {
      throw profileError;
    }

    return {
      logged_in: true,
      user: {
        id: user.id,
        name: profileData?.name || user.user_metadata?.full_name || user.email?.split('@')[0],
        email: user.email,
        role: profileData?.role || 'user',
        avatar: profileData?.avatar || user.user_metadata?.avatar_url || null
      },
      role: profileData?.role || 'user'
    };
  }

  function isConfigured() {
    const { supabaseUrl, supabaseAnonKey } = getConfig();
    return Boolean(supabaseUrl && supabaseAnonKey && !supabaseUrl.includes('YOUR_PROJECT_REF'));
  }

  window.UrbanThreadSupabase = {
    isConfigured,
    getClient,
    ensureProfile,
    signIn,
    signUp,
    signOut,
    getCurrentUser,
    getSessionState
  };
})();
