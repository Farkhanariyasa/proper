import { redirect } from 'next/navigation';

// Dashboard operator & pimpinan digabung menjadi satu halaman /dashboard
export default function DashboardPimpinanPage() {
  redirect('/dashboard');
}
