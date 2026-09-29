import { getTasks } from "@/services/tasks";
import { createTask } from "./actions";

export default async function TasksPage() {
  const tasks = await getTasks();

  return (
    <main className="mx-auto max-w-xl p-8">
      <h1 className="mb-6 text-2xl font-bold">Tasks</h1>

      <form action={createTask} className="mb-8 flex gap-2">
        <input
          type="text"
          name="title"
          placeholder="Judul task"
          required
          className="flex-1 rounded border px-3 py-2"
        />
        <button
          type="submit"
          className="rounded bg-black px-4 py-2 text-white"
        >
          Tambah
        </button>
      </form>

      <ul className="space-y-2">
        {tasks.map((task) => (
          <li key={task.id} className="rounded border p-3">
            <p className="font-medium">{task.title}</p>
            {task.description && (
              <p className="text-sm text-gray-500">{task.description}</p>
            )}
            <p className="text-xs text-gray-400">
              {task.is_done ? "Selesai" : "Belum selesai"}
            </p>
          </li>
        ))}
      </ul>
    </main>
  );
}
