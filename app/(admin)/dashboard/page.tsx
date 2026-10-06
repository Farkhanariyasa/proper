import { Suspense } from 'react';
import PublicDashboard from '@/components/public/PublicDashboard';

// Isi dashboard admin sama dengan dashboard di landing page
export default function DashboardPage() {
  return (
    <Suspense>
      <PublicDashboard />
    </Suspense>
  );
}
