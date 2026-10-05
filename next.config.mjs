/** @type {import('next').NextConfig} */
const nextConfig = {
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
