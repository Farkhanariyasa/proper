import type { NextConfig } from "next";

const nextConfig: NextConfig = {
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
