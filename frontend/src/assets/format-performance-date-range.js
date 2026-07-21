import { format } from "date-fns";

// Groups consecutive-day performance dates into runs and formats each as
// "Apr 17 - 19", "Apr 17", or "Mar 29 - Apr 2", joined with " & ".
export default function formatPerformanceDateRange(dates) {
  const sorted = [...dates].sort((a, b) => a - b);

  const runs = [];
  let run = [sorted[0]];

  for (let i = 1; i < sorted.length; i++) {
    const diffMs = sorted[i] - sorted[i - 1];
    const diffDays = Math.round(diffMs / 86400000);
    if (diffDays === 1) {
      run.push(sorted[i]);
    } else {
      runs.push(run);
      run = [sorted[i]];
    }
  }
  runs.push(run);

  const formatted = runs.map((run) => {
    const first = run[0];
    const last = run[run.length - 1];

    if (run.length === 1) return format(first, "MMM d");

    if (format(first, "MMM") === format(last, "MMM")) {
      return `${format(first, "MMM d")} - ${format(last, "d")}`;
    }

    return `${format(first, "MMM d")} - ${format(last, "MMM d")}`;
  });

  return formatted.join(" & ");
}
