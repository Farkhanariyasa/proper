import type { Metadata } from 'next';
import { Plus_Jakarta_Sans } from 'next/font/google';
import './globals.css';
import { AuthProvider } from '@/context/AuthContext';

const plusJakartaSans = Plus_Jakarta_Sans({
  variable: '--font-plus-jakarta-sans',
  subsets: ['latin'],
  display: 'swap',
});

export const metadata: Metadata = {
  title: 'Proper',
  description:
    'Menghubungkan pencari kerja, perusahaan, dan petugas pengantar kerja melalui data skill yang terstandar didukung Skill Taxonomy Nasional.',
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html
      lang="id"
      className={`${plusJakartaSans.variable} h-full antialiased scroll-smooth`}
    >
      <body className="min-h-full font-sans bg-[#F8FAFC] text-slate-800">
        <AuthProvider>{children}</AuthProvider>
      </body>
    </html>
  );
}
