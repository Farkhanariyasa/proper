"use server";

import { revalidatePath } from "next/cache";

const API_URL = process.env.API_URL ?? "http://localhost:8000";

export async function createTask(formData: FormData) {
  const title = formData.get("title");
  const description = formData.get("description");

  const res = await fetch(`${API_URL}/api/tasks`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
    },
    body: JSON.stringify({ title, description }),
  });

  if (!res.ok) {
    throw new Error(`Failed to create task (${res.status})`);
  }

  revalidatePath("/tasks");
}
