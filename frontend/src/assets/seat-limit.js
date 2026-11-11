// Seats left for a performance on the purchase page (seats_remaining comes
// from ShowController::homeShows(), i.e. Performance::seatsRemaining()).
// Undefined when not known — e.g. a performance loaded somewhere else —
// in which case only the server's own check applies.
export const seatLimit = (performance) => {
  const n = performance?.seats_remaining;
  return n == null ? undefined : Number(n);
};

const seats = (n) => `${n} ${n === 1 ? "seat" : "seats"}`;

// Rules for a "Number of Tickets" field: at least 1, and no more than the
// seats left (the same cap TicketSaleController::publicStore() enforces).
export const quantityRules = (performance) => [
  (val) => val >= 1 || "Must be at least 1",
  (val) => {
    const max = seatLimit(performance);
    if (max == null || val <= max) return true;
    return max > 0 ? `Only ${seats(max)} left for this performance` : "No seats left for this performance";
  },
];
