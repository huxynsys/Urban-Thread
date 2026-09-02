window.UT_CONFIG = {
  supabaseUrl: 'https://YOUR_PROJECT_REF.supabase.co',
  supabaseAnonKey: 'YOUR_SUPABASE_ANON_KEY',
  appName: 'Urban Thread',
  authRedirectUrl: window.location.origin + '/reg.php'
};

window.UT_ENV = {
  isSupabaseConfigured: () => {
    return Boolean(
      window.UT_CONFIG &&
      window.UT_CONFIG.supabaseUrl &&
      window.UT_CONFIG.supabaseUrl !== 'https://YOUR_PROJECT_REF.supabase.co' &&
      window.UT_CONFIG.supabaseAnonKey &&
      window.UT_CONFIG.supabaseAnonKey !== 'YOUR_SUPABASE_ANON_KEY'
    );
  }
};
