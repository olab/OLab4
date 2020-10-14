const defaultConfig = {
  /**
   * Returns base name(href) string using for for constructing route's paths
   *
   * @returns {string | undefined | string}
   */
  baseHref: process.env.PUBLIC_URL || '/',
};

export default defaultConfig;
