import { Task } from "@/types/task";

const API_URL = process.env.API_URL ?? "http://localhost:8000";

export async function getTasks(): Promise<Task[]> {
  const res = await fetch(`${API_URL}/api/tasks`, {
    headers: { Accept: "application/json" },
    cache: "no-store",
  });

  if (!res.ok) {
    throw new Error(`Failed to fetch tasks (${res.status})`);
  }

  return res.json();
}
