/** @type {import('next').NextConfig} */
const nextConfig = {
  output: 'standalone',
  basePath: '/pembelajaran-lmi',
  async redirects() {
    return [
      {
        source: '/',
        destination: '/pembelajaran-lmi',
        basePath: false,
        permanent: false,
      },
    ];
  },
};

export default nextConfig;
