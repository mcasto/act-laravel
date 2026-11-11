<template>
  <div>
    <div v-if="shows.length === 0" class="text-grey-6 q-pa-md">
      No ticket sales on record.
    </div>

    <q-table
      v-else
      :rows="filteredRecs"
      :columns="columns"
      row-key="id"
      dense
      v-model:pagination="pagination"
      :table-row-class-fn="
        (row) => (isFullyCancelled(row) ? 'row-cancelled' : '')
      "
    >
      <template #top>
        <div class="full-width">
          <div class="flex no-wrap items-center q-gutter-x-sm q-mb-sm">
            <q-select
              :options="shows"
              v-model="show"
              :label="show ? 'Show' : 'Select Show'"
              dense
              outlined
              stack-label
              style="min-width: 8rem;"
            />
            <q-space />
            <template v-if="show">
              <q-toggle
                label="Send Emails"
                v-model="store.send_mail"
                :false-value="0"
                :true-value="1"
                :disable="isReadOnly"
              >
                <q-tooltip>
                  Default for the "Send Emails" toggle on the New Ticket Sale
                  form — doesn't send anything by itself. Turn off if you're
                  catching up on bulk data entry.
                </q-tooltip>
              </q-toggle>
              <q-btn
                :icon="matInfo"
                flat
                round
                dense
                size="sm"
                @click="sendMailInfoDialog = true"
              ></q-btn>
              <q-btn
                :icon="matAdd"
                size="sm"
                color="primary"
                round
                :disable="isReadOnly"
                @click="onNewTicket"
              />
              <AdminTicketSalesPrint
                :recs="recs"
                :ticket-price="ticketPrice"
                :show-name="show.label"
              />
              <q-btn
                :icon="mdiFileExcel"
                color="positive"
                flat
                :loading="exporting"
                @click="exportSales"
              >
                <q-tooltip>
                  Export to Excel (Sales, Tickets and Mailchimp sheets) — honors
                  the Performance and Payment Method search criteria, not the
                  others
                </q-tooltip>
              </q-btn>
              <q-btn
                :icon="mdiCashClock"
                color="warning"
                flat
                @click="unconfirmedDialog = true"
              >
                <q-badge v-if="unconfirmedRecs.length" color="negative" floating>
                  {{ unconfirmedRecs.length }}
                </q-badge>
                <q-tooltip>Pending PayPal / Bank Transfer Payments</q-tooltip>
              </q-btn>
              <q-btn :icon="matEvent" color="info" flat>
                <q-tooltip>Tickets Sold By Date</q-tooltip>
                <q-menu>
                  <q-list dense style="min-width: 380px;">
                    <q-item-label header class="text-weight-bold"
                      >Tickets Sold By Date</q-item-label
                    >
                    <q-item v-if="ticketsByDate.length">
                      <q-item-section />
                      <q-item-section
                        side
                        class="text-caption text-grey-7 tsbd-col"
                        >Confirmed</q-item-section
                      >
                      <q-item-section
                        side
                        class="text-caption text-grey-7 tsbd-col"
                        >Reserved</q-item-section
                      >
                      <q-item-section
                        side
                        class="text-caption text-grey-7 tsbd-rev"
                        >Projected</q-item-section
                      >
                    </q-item>
                    <q-item v-for="row in ticketsByDate" :key="row.id">
                      <q-item-section>{{ row.label }}</q-item-section>
                      <q-item-section side class="tsbd-col">
                        {{ row.confirmed }}
                      </q-item-section>
                      <q-item-section side class="text-weight-medium tsbd-col">
                        {{ row.total }}
                      </q-item-section>
                      <q-item-section side class="text-positive tsbd-rev">
                        ${{ row.revenue.toFixed(2) }}
                      </q-item-section>
                    </q-item>
                    <div
                      v-if="ticketsByDate.length === 0"
                      class="text-caption text-grey-7 q-pa-sm"
                    >
                      No reservations yet.
                    </div>
                    <q-separator v-if="ticketsByDate.length" />
                    <q-item v-if="ticketsByDate.length">
                      <q-item-section class="text-weight-bold"
                        >Total</q-item-section
                      >
                      <q-item-section side class="text-weight-bold tsbd-col">
                        {{ confirmedTicketCount }}
                      </q-item-section>
                      <q-item-section side class="text-weight-bold tsbd-col">
                        {{ totalTickets }}
                      </q-item-section>
                      <q-item-section
                        side
                        class="text-positive text-weight-bold tsbd-rev"
                      >
                        ${{ projectedRevenueAll.toFixed(2) }}
                      </q-item-section>
                    </q-item>
                  </q-list>
                </q-menu>
              </q-btn>
              <q-btn :icon="mdiCashMultiple" color="positive" flat>
                <q-tooltip>Projected Revenue</q-tooltip>
                <q-menu>
                  <q-list dense style="min-width: 260px;">
                    <q-item-label header class="text-weight-bold"
                      >Projected Revenue</q-item-label
                    >
                    <q-item v-for="row in revenueByMethod" :key="row.label">
                      <q-item-section side>
                        <div
                          :style="`width: 10px; height: 10px; border-radius: 50%; background: ${row.color};`"
                        ></div>
                      </q-item-section>
                      <q-item-section>{{ row.label }}</q-item-section>
                      <q-item-section side
                        >{{ row.count }} × ${{
                          effectivePrice(row.multiplier)
                        }}</q-item-section
                      >
                      <q-item-section
                        side
                        class="text-positive text-weight-medium"
                      >
                        ${{ row.revenue.toFixed(2) }}
                      </q-item-section>
                    </q-item>
                    <q-separator />
                    <q-item>
                      <q-item-section class="text-weight-bold"
                        >Total</q-item-section
                      >
                      <q-item-section side class="text-weight-bold">
                        {{ totalTickets }} tickets
                      </q-item-section>
                      <q-item-section
                        side
                        class="text-positive text-weight-bold"
                      >
                        ${{ totalRevenue.toFixed(2) }}
                      </q-item-section>
                    </q-item>
                    <q-item>
                      <q-item-section class="text-caption text-grey-7">
                        % Sold Out
                      </q-item-section>
                      <q-item-section side class="text-caption text-grey-7">
                        {{ totalTickets }} / {{ soldOutCapacity }} ({{
                          soldOutPct
                        }}%)
                      </q-item-section>
                    </q-item>
                  </q-list>
                </q-menu>
              </q-btn>
            </template>
          </div>
          <div class="flex no-wrap items-center q-gutter-x-md">
            <q-select
              v-if="show"
              :options="performanceOptions"
              v-model="performanceFilter"
              label="Performance Date"
              dense
              outlined
              clearable
              emit-value
              map-options
              style="min-width: 220px;"
            />
            <q-btn
              :icon="matSearch"
              label="Search"
              color="primary"
              outline
              no-caps
              :disable="!show"
              @click="openSearchDialog"
            />
            <div class="flex items-center q-gutter-xs">
              <q-chip
                v-for="chip in criteriaChips"
                :key="chip.key"
                dense
                removable
                color="primary"
                text-color="white"
                @remove="removeCriterion(chip.key)"
              >
                {{ chip.label }}
              </q-chip>
              <q-btn
                v-if="criteriaChips.length > 1"
                label="Clear all"
                flat
                dense
                no-caps
                size="sm"
                color="primary"
                @click="criteria = {}"
              />
            </div>
            <q-space />
            <template v-if="show">
              <div
                v-for="(color, label) in paymentMethodColors"
                :key="label"
                class="flex items-center q-gutter-x-xs"
              >
                <div
                  :style="`width: 10px; height: 10px; border-radius: 50%; background: ${color};`"
                ></div>
                <span class="text-caption">{{ label }}</span>
              </div>
            </template>
          </div>
          <div class="flex justify-end">
            <q-pagination
              v-if="pagesNumber > 1"
              v-model="pagination.page"
              :max="pagesNumber"
              :max-pages="6"
              boundary-links
              direction-links
              size="sm"
              class="q-mt-sm"
            />
          </div>
        </div>
      </template>

      <template #header-cell-payment_method="props">
        <q-th :props="props">
          <q-icon :name="mdiCurrencyUsd" />
        </q-th>
      </template>

      <template #header-cell-front_row="props">
        <q-th :props="props">
          FR
          <q-tooltip>Front Row</q-tooltip>
        </q-th>
      </template>

      <template #header-cell-actions="props">
        <q-th :props="props">
          <q-icon :name="mdiCog" />
        </q-th>
      </template>

      <template #body-cell-no_show="props">
        <q-td :props="props" class="text-center">
          <q-input
            :model-value="props.row.no_show || 0"
            type="number"
            min="0"
            :max="activeQty(props.row)"
            dense
            borderless
            input-class="text-center"
            style="width: 56px; display: inline-block"
            :class="{ 'text-negative text-weight-bold': props.row.no_show > 0 }"
            :disable="isReadOnly"
            debounce="500"
            @update:model-value="(value) => updateNoShow(props.row, value)"
          >
            <q-tooltip>
              How many of this party of {{ activeQty(props.row) }} didn't show
            </q-tooltip>
          </q-input>
        </q-td>
      </template>

      <template #body-cell-cancelled="props">
        <q-td :props="props" class="text-center">
          <q-input
            :model-value="props.row.cancelled || 0"
            type="number"
            min="0"
            :max="props.row.quantity || 1"
            dense
            borderless
            input-class="text-center"
            style="width: 56px; display: inline-block"
            :class="{
              'text-negative text-weight-bold': props.row.cancelled > 0,
            }"
            :disable="isReadOnly"
            debounce="500"
            @update:model-value="(value) => updateCancelled(props.row, value)"
          >
            <q-tooltip>
              How many of this sale's {{ props.row.quantity || 1 }} tickets were
              cancelled. The sale is kept either way — only delete a sale
              entered by mistake.
            </q-tooltip>
          </q-input>
          <q-icon
            v-if="props.row.cancelled_returned > 0"
            :name="mdiUndoVariant"
            color="positive"
            size="xs"
          >
            <q-tooltip>
              {{
                props.row.payment_method?.value === "comp"
                  ? "Comp returned to the patron"
                  : `${props.row.cancelled_returned} returned to the Flex balance`
              }}
            </q-tooltip>
          </q-icon>
        </q-td>
      </template>

      <template #body-cell-resold="props">
        <q-td :props="props" class="text-center">
          <q-input
            v-if="props.row.cancelled > 0"
            :model-value="props.row.resold || 0"
            type="number"
            min="0"
            :max="props.row.cancelled"
            dense
            borderless
            input-class="text-center"
            style="width: 56px; display: inline-block"
            :class="{ 'text-positive text-weight-bold': props.row.resold > 0 }"
            :disable="isReadOnly"
            debounce="500"
            @update:model-value="(value) => updateResold(props.row, value)"
          >
            <q-tooltip>
              How many of the {{ props.row.cancelled }} cancelled seats were put
              back on sale. Cancelled seats that aren't resold still count as
              taken.
            </q-tooltip>
          </q-input>
        </q-td>
      </template>

      <template #body-cell-confirmed="props">
        <q-td :props="props" class="text-center">
          <q-toggle
            :model-value="!!props.row.confirmed"
            color="positive"
            dense
            :disable="isReadOnly"
            @update:model-value="(value) => updatePaid(props.row, value)"
          />
        </q-td>
      </template>

      <template #body-cell-tickets="props">
        <q-td :props="props" class="text-center" :class="{ 'bg-green-2': allTicketsRedeemed(props.row) }">
          <span
            v-if="props.row.tickets?.length"
            class="text-primary cursor-pointer text-weight-medium"
            @click="openTicketsDialog(props.row)"
          >
            {{ ticketNumbersDisplay(props.row) }}
          </span>
          <span v-else>{{ ticketNumbersDisplay(props.row) }}</span>
        </q-td>
      </template>

      <template #body-cell-payment_method="props">
        <q-td :props="props" class="text-center">
          <q-icon
            :name="mdiCircle"
            :style="`color: ${
              paymentMethodColors[props.row.payment_method.label]
            };`"
          >
            <q-tooltip>{{ props.row.payment_method.label }}</q-tooltip>
          </q-icon>
        </q-td>
      </template>

      <template #body-cell-actions="props">
        <q-td :props="props">
          <q-icon
            v-if="props.row.reason_changed"
            :name="matHistory"
            color="grey-7"
            size="xs"
            class="q-mr-xs"
          >
            <q-tooltip>{{ props.row.reason_changed }}</q-tooltip>
          </q-icon>
          <q-btn
            :icon="matEdit"
            flat
            round
            color="primary"
            size="sm"
            :disable="isReadOnly"
            @click="onEditSale(props.row)"
          />
          <q-btn
            :icon="mdiCalendarSync"
            flat
            round
            color="primary"
            size="sm"
            :disable="isReadOnly || !Number(props.row.quantity)"
            @click="openReschedule(props.row)"
          >
            <q-tooltip>
              {{
                Number(props.row.quantity)
                  ? "Reschedule to another performance"
                  : "Already rescheduled"
              }}
            </q-tooltip>
          </q-btn>
          <q-btn
            :icon="matDelete"
            flat
            round
            color="negative"
            size="sm"
            :disable="isReadOnly"
            @click="onDelete(props.row)"
          />
        </q-td>
      </template>

      <template #bottom>
        <div class="flex justify-end full-width">
          <q-pagination
            v-if="pagesNumber > 1"
            v-model="pagination.page"
            :max="pagesNumber"
            :max-pages="6"
            boundary-links
            direction-links
            size="sm"
          />
        </div>
      </template>
    </q-table>

    <q-dialog v-model="searchDialog">
      <q-card style="width: 640px; max-width: 95vw">
        <q-form @submit.prevent="applySearch">
          <q-card-section>
            <div class="text-h6">Search Ticket Sales</div>
            <div class="text-caption text-grey-7">
              {{ show?.label }} — fill in any fields; a sale must match all of
              them.
            </div>
          </q-card-section>

          <q-card-section class="q-pt-none">
            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-6">
                <q-input
                  v-model="draft.name"
                  label="Name"
                  hint="Door name or purchaser"
                  dense
                  outlined
                  autofocus
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-input v-model="draft.email" label="Email" dense outlined />
              </div>
              <div class="col-12 col-sm-6">
                <q-input v-model="draft.phone" label="Phone" dense outlined />
              </div>
              <div class="col-12 col-sm-6">
                <q-input
                  v-model="draft.ticket"
                  label="Ticket #"
                  hint="e.g. 7, 007 or #007"
                  dense
                  outlined
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-select
                  v-model="draft.performance_id"
                  :options="performanceOptions"
                  label="Performance"
                  dense
                  outlined
                  clearable
                  emit-value
                  map-options
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-select
                  v-model="draft.payment_method_id"
                  :options="paymentMethodOptions"
                  label="Payment Method"
                  dense
                  outlined
                  clearable
                  emit-value
                  map-options
                >
                  <template #option="scope">
                    <q-item v-bind="scope.itemProps">
                      <q-item-section side>
                        <div
                          :style="`width: 10px; height: 10px; border-radius: 50%; background: ${scope.opt.color};`"
                        ></div>
                      </q-item-section>
                      <q-item-section>{{ scope.opt.label }}</q-item-section>
                    </q-item>
                  </template>
                </q-select>
              </div>
              <div class="col-12 col-sm-6">
                <q-select
                  v-model="draft.angel_level"
                  :options="angelLevelOptions"
                  label="Angel Level"
                  hint="Current season"
                  dense
                  outlined
                  clearable
                  emit-value
                  map-options
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-input
                  v-model="draft.notes"
                  label="Special Seating / Comments"
                  dense
                  outlined
                />
              </div>
              <div class="col-6 col-sm-3">
                <q-input
                  v-model="draft.sold_from"
                  type="date"
                  label="Sold From"
                  stack-label
                  dense
                  outlined
                />
              </div>
              <div class="col-6 col-sm-3">
                <q-input
                  v-model="draft.sold_to"
                  type="date"
                  label="Sold To"
                  stack-label
                  dense
                  outlined
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-select
                  v-model="draft.confirmed"
                  :options="yesNoOptions"
                  label="Paid"
                  dense
                  outlined
                  clearable
                  emit-value
                  map-options
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-select
                  v-model="draft.no_show"
                  :options="yesNoOptions"
                  label="Has No Shows"
                  dense
                  outlined
                  clearable
                  emit-value
                  map-options
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-select
                  v-model="draft.cancelled"
                  :options="yesNoOptions"
                  label="Has Cancellations"
                  dense
                  outlined
                  clearable
                  emit-value
                  map-options
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-select
                  v-model="draft.front_row"
                  :options="yesNoOptions"
                  label="Front Row"
                  dense
                  outlined
                  clearable
                  emit-value
                  map-options
                />
              </div>
            </div>
          </q-card-section>

          <q-card-actions align="right">
            <q-btn flat label="Clear" @click="draft = {}" />
            <q-btn flat label="Cancel" v-close-popup />
            <q-btn type="submit" flat label="Search" color="primary" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <q-dialog v-model="rescheduleDialog" persistent>
      <q-card style="width: 460px; max-width: 95vw">
        <q-card-section>
          <div class="text-h6">Reschedule</div>
          <div v-if="rescheduleRow" class="text-caption text-grey-7">
            {{ doorName(rescheduleRow) }} —
            {{ rescheduleRow.quantity }} ticket{{
              rescheduleRow.quantity == 1 ? "" : "s"
            }}, now
            {{ rescheduleRow.performance.formatted_date }}
            {{ rescheduleRow.performance.formatted_time }}
          </div>
        </q-card-section>

        <q-card-section class="q-pt-none q-gutter-y-md">
          <q-select
            v-model="rescheduleTo"
            :options="rescheduleOptions"
            :loading="rescheduleLoading"
            label="New performance"
            dense
            outlined
            emit-value
            map-options
            option-disable="disable"
          />
          <q-input
            v-model="rescheduleNote"
            label="Note (optional)"
            hint="e.g. Was sick — added to the reason on both records"
            dense
            outlined
            maxlength="500"
          />
          <div class="text-caption text-grey-7">
            The purchase moves to the new performance with its ticket numbers.
            The original is kept with quantity 0, and both get a reason for the
            change.
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn
            flat
            label="Cancel"
            :disable="rescheduleSaving"
            v-close-popup
          />
          <q-btn
            flat
            label="Reschedule"
            color="primary"
            :loading="rescheduleSaving"
            :disable="!rescheduleTo"
            @click="saveReschedule"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="sendMailInfoDialog">
      <q-card style="max-width: 420px;">
        <q-card-section>
          <div class="text-h6">About "Send Emails"</div>
        </q-card-section>
        <q-card-section class="q-pt-none">
          This sets the default for the "Send Emails" toggle on the New
          Ticket Sale form — it doesn't send anything by itself. When a new
          sale is saved with that toggle on, two emails go out right away:
          one to the box office letting you know a sale came in, and one to
          the ticket buyer confirming their purchase.
          <br /><br />
          Turn this off if you're catching up on bulk data entry and don't
          want those emails going out for sales you're just recording after
          the fact — it'll stay off for every New Ticket Sale form you open
          until you turn it back on.
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Got it" color="primary" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="unconfirmedDialog">
      <q-card style="min-width: 360px; max-width: 900px; width: 100%;">
        <q-card-section class="flex items-center">
          <div>
            <div class="text-h6">Pending Confirmation</div>
            <div class="text-caption text-grey-7">
              {{ show?.label }} — PayPal and Bank Transfers
            </div>
          </div>
          <q-space />
          <q-btn
            v-if="unconfirmedRecs.length"
            :icon="matEmail"
            label="Send Reminder"
            flat
            dense
            color="primary"
            :disable="isReadOnly || reminderSelected.length === 0"
            @click="openReminderDialog"
          >
            <q-tooltip>
              Emails each checked patron individually
            </q-tooltip>
          </q-btn>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <div
            v-if="unconfirmedRecs.length === 0 && !unconfirmedCash.sales"
            class="text-grey-7"
          >
            Nothing outstanding — every PayPal and Bank Transfer payment is
            confirmed.
          </div>
          <q-markup-table v-else dense flat bordered wrap-cells>
            <thead>
              <tr>
                <th class="text-center">
                  <q-checkbox
                    :model-value="reminderAllState"
                    dense
                    :disable="isReadOnly"
                    @update:model-value="toggleAllReminders"
                  />
                </th>
                <th class="text-left">Performance</th>
                <th class="text-left">Purchaser</th>
                <th class="text-left">Contact</th>
                <th class="text-center">Qty</th>
                <th class="text-center">Tickets</th>
                <th class="text-left">Method</th>
                <th class="text-left">Date Sold</th>
                <th class="text-left">Transfer Date</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in unconfirmedRecs" :key="row.id">
                <td class="text-center">
                  <q-checkbox
                    v-model="reminderSelected"
                    :val="row.id"
                    dense
                    :disable="isReadOnly"
                  />
                </td>
                <td>
                  {{ row.performance.formatted_date }}
                  {{ row.performance.formatted_time }}
                </td>
                <td>{{ row.patron.first_name }} {{ row.patron.last_name }}</td>
                <td>
                  <span v-if="isReadOnly">{{ row.patron.email }}</span>
                  <a
                    v-else
                    :href="`mailto:${row.patron.first_name} ${row.patron.last_name} <${row.patron.email}>`"
                    >{{ row.patron.email }}</a
                  >
                  <div v-if="row.patron.phone" class="text-caption">
                    {{ row.patron.phone }}
                  </div>
                </td>
                <td class="text-center">{{ activeQty(row) }}</td>
                <td class="text-center">{{ ticketNumbersDisplay(row) }}</td>
                <td>{{ row.payment_method.label }}</td>
                <td>{{ format(parseISO(row.sold_at), "PP") }}</td>
                <td>
                  {{
                    row.transfer_date
                      ? format(parseISO(row.transfer_date), "PP")
                      : ""
                  }}
                </td>
                <td>
                  <q-btn
                    :icon="matEdit"
                    flat
                    round
                    color="primary"
                    size="sm"
                    :disable="isReadOnly"
                    @click="onEditSale(row)"
                  >
                    <q-tooltip>Edit / mark confirmed</q-tooltip>
                  </q-btn>
                </td>
              </tr>
              <tr v-if="unconfirmedCash.sales" class="text-weight-medium">
                <td></td>
                <td>All performances</td>
                <td>Cash/Door</td>
                <td class="text-grey-7">
                  {{ unconfirmedCash.sales }}
                  {{ unconfirmedCash.sales === 1 ? "sale" : "sales" }}
                </td>
                <td class="text-center">{{ unconfirmedCash.qty }}</td>
                <td></td>
                <td>Cash / Door</td>
                <td colspan="2"></td>
                <td>
                  <q-btn
                    :icon="matListAlt"
                    flat
                    round
                    color="primary"
                    size="sm"
                    @click="cashDoorDialog = true"
                  >
                    <q-tooltip>Breakdown by performance</q-tooltip>
                  </q-btn>
                </td>
              </tr>
            </tbody>
          </q-markup-table>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Close" color="primary" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="cashDoorDialog">
      <q-card style="min-width: 320px; max-width: 500px; width: 100%;">
        <q-card-section>
          <div class="text-h6">Cash / Door</div>
          <div class="text-caption text-grey-7">
            {{ show?.label }} — unconfirmed, by performance
          </div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-markup-table dense flat bordered>
            <thead>
              <tr>
                <th class="text-left">Performance</th>
                <th class="text-center">Sales</th>
                <th class="text-center">Qty</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="perf in unconfirmedCash.byPerformance" :key="perf.id">
                <td>{{ perf.formatted_date }} {{ perf.formatted_time }}</td>
                <td class="text-center">{{ perf.sales }}</td>
                <td class="text-center">{{ perf.qty }}</td>
              </tr>
              <tr class="text-weight-bold">
                <td>Total</td>
                <td class="text-center">{{ unconfirmedCash.sales }}</td>
                <td class="text-center">{{ unconfirmedCash.qty }}</td>
              </tr>
            </tbody>
          </q-markup-table>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Close" color="primary" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="reminderDialog" persistent>
      <q-card style="min-width: 360px; max-width: 600px; width: 100%;">
        <q-card-section>
          <div class="text-h6">Send Payment Reminder</div>
          <div class="text-caption text-grey-7">
            Goes to {{ reminderPatronCount }} patron{{
              reminderPatronCount === 1 ? "" : "s"
            }}, each as their own email. Each one starts with "Hello
            <i>name</i>," and ends with a list of their pending reservations,
            so just write the part in between. Each patron's ticket numbers
            are added to the end of the subject as "(Reference: …)".
          </div>
        </q-card-section>

        <q-card-section class="q-pt-none q-gutter-y-md">
          <q-input
            v-model="reminderSubject"
            label="Subject"
            outlined
            dense
            maxlength="255"
          />
          <q-input
            v-model="reminderBody"
            label="Message"
            type="textarea"
            outlined
            autogrow
            maxlength="5000"
          />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn
            flat
            label="Cancel"
            :disable="reminderSending"
            v-close-popup
          />
          <q-btn
            flat
            label="Send"
            color="primary"
            :loading="reminderSending"
            :disable="!reminderSubject.trim() || !reminderBody.trim()"
            @click="sendReminders"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="ticketsDialog">
      <q-card style="min-width: 350px;">
        <q-card-section>
          <div class="text-h6">Tickets</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-list separator>
            <q-item v-for="ticket in ticketsDialogRows" :key="ticket.id">
              <q-item-section side>
                <div class="text-weight-medium">#{{ ticket.formatted_number }}</div>
              </q-item-section>
              <q-item-section>{{ ticket.name }}</q-item-section>
              <q-item-section side>
                <q-checkbox
                  v-model="ticket.redeemed"
                  label="Redeemed"
                  :disable="isReadOnly"
                />
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancel" v-close-popup />
          <q-btn
            flat
            label="Save"
            color="primary"
            :disable="isReadOnly"
            @click="saveTicketRedemptions"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import {
  matAdd,
  matDelete,
  matEdit,
  matEmail,
  matEvent,
  matInfo,
  matHistory,
  matListAlt,
  matSearch,
} from "@quasar/extras/material-icons";
import {
  mdiCashClock,
  mdiCalendarSync,
  mdiCashMultiple,
  mdiCircle,
  mdiCog,
  mdiCurrencyUsd,
  mdiFileExcel,
  mdiUndoVariant,
} from "@quasar/extras/mdi-v7";
import { format, parseISO } from "date-fns";
import { sortBy, uniqBy } from "lodash-es";
import { Dialog, Notify } from "quasar";
import callApi from "src/assets/call-api";
import getPermissionLevel from "src/assets/get-permission-level";
import { useStore } from "src/stores/store";
import { computed, onMounted, ref, watch } from "vue";
import AdminTicketSalesPrint from "./AdminTicketSalesPrint.vue";

const store = useStore();

const isReadOnly = computed(
  () => getPermissionLevel(store.admin.user, "ticket-sales") === "read-only",
);

const sendMailInfoDialog = ref(false);

// Downloads the .xlsx from TicketSaleExportController. Done with fetch
// rather than callApi, which only handles JSON responses.
const exporting = ref(false);
const exportSales = async () => {
  if (!show.value) return;
  exporting.value = true;
  try {
    const params = new URLSearchParams({ show_id: show.value.value });
    if (criteria.value.performance_id)
      params.set("performance_id", criteria.value.performance_id);
    if (criteria.value.payment_method_id)
      params.set("payment_method_id", criteria.value.payment_method_id);

    const response = await fetch(`/api/ticket-sales/export?${params}`, {
      headers: {
        Authorization: `Bearer ${store.admin.user?.token}`,
        Accept: "application/json",
      },
    });
    if (!response.ok) throw new Error(`Export failed (${response.status})`);

    const filename =
      response.headers
        .get("Content-Disposition")
        ?.match(/filename="?([^";]+)"?/)?.[1] ?? "ticket-sales.xlsx";
    const url = URL.createObjectURL(await response.blob());
    const link = document.createElement("a");
    link.href = url;
    link.download = filename;
    link.click();
    URL.revokeObjectURL(url);
  } catch (error) {
    console.error(error);
    Notify.create({ type: "negative", message: "Couldn't export ticket sales." });
  } finally {
    exporting.value = false;
  }
};

const currentShow = store.home.currentShow;

const show = ref(
  store.admin.selectedShow ??
    (currentShow ? { label: currentShow.name, value: currentShow.id } : null),
);

watch(show, (val) => {
  store.admin.selectedShow = val;
});

// Search criteria from the Search dialog live in the store
// (sessionStorage-persisted) so they survive the trip to the add/edit
// ticket form and back, and stay set until cleared. Keys:
//   name, email, phone, notes — case-insensitive "contains"
//   ticket — a ticket number, matching the sale that holds it
//   performance_id, payment_method_id, angel_level — exact
//   confirmed, no_show, cancelled, front_row — "yes" | "no"
//   sold_from, sold_to — "YYYY-MM-DD", inclusive
// Empty values are dropped on apply, so a key present = an active filter.
const criteria = computed({
  get: () => store.ticketSalesCriteria ?? {},
  set: (val) => {
    store.ticketSalesCriteria = val;
  },
});

// The performance belongs to the previous show, so it no longer applies.
// Everything else (payment method, names, …) still means something.
watch(show, () => {
  if (criteria.value.performance_id) {
    const { performance_id: _dropped, ...rest } = criteria.value;
    criteria.value = rest;
  }
});

const searchDialog = ref(false);
const draft = ref({});

const openSearchDialog = () => {
  draft.value = { ...criteria.value };
  searchDialog.value = true;
};

const applySearch = () => {
  criteria.value = Object.fromEntries(
    Object.entries(draft.value).filter(
      ([, v]) => v !== null && v !== undefined && String(v).trim() !== "",
    ),
  );
  searchDialog.value = false;
};

const removeCriterion = (key) => {
  const { [key]: _removed, ...rest } = criteria.value;
  criteria.value = rest;
};

const yesNoOptions = [
  { label: "Yes", value: "yes" },
  { label: "No", value: "no" },
];

const CRITERIA_LABELS = {
  name: "Name",
  email: "Email",
  phone: "Phone",
  ticket: "Ticket #",
  performance_id: "Performance",
  payment_method_id: "Payment",
  angel_level: "Angel",
  notes: "Seating/Comments",
  sold_from: "Sold from",
  sold_to: "Sold to",
  confirmed: "Paid",
  no_show: "Has no-shows",
  cancelled: "Has cancellations",
  front_row: "Front row",
};

// The toolbar's Performance Date dropdown — the same performance_id
// criterion the Search dialog sets, so the two always agree.
const performanceFilter = computed({
  get: () => criteria.value.performance_id ?? null,
  set: (val) => {
    const { performance_id: _old, ...rest } = criteria.value;
    criteria.value = val ? { ...rest, performance_id: val } : rest;
  },
});

// The performance shows in its dropdown, so it gets no chip.
const criteriaChips = computed(() =>
  Object.entries(criteria.value)
    .filter(([key]) => key !== "performance_id")
    .map(([key, value]) => {
      let shown = value;
      if (key === "performance_id") {
        shown =
          performanceOptions.value.find((o) => o.value === value)?.label ??
          value;
      } else if (key === "payment_method_id") {
        shown =
          paymentMethodOptions.value.find((o) => o.value === value)?.label ??
          value;
      } else if (
        ["confirmed", "no_show", "cancelled", "front_row"].includes(key)
      ) {
        shown = value === "yes" ? "Yes" : "No";
      } else if (key === "sold_from" || key === "sold_to") {
        shown = format(parseISO(value), "PP");
      }
      return { key, label: `${CRITERIA_LABELS[key] ?? key}: ${shown}` };
    }),
);

const pagination = ref({ page: 1, rowsPerPage: 12, sortBy: "last", descending: false });
// Back to page 1 when the result set changes, or a later page may be empty.
watch([criteria, show], () => {
  pagination.value.page = 1;
});

const pagesNumber = computed(() =>
  Math.ceil(filteredRecs.value.length / pagination.value.rowsPerPage),
);

// A ticket_sales row (including a redeemed comp, which gets a real row of
// its own — see CompTixController::redeemComp()) carries its individual
// tickets in `tickets`. Legacy rows predating per-ticket tracking have
// neither `tickets` nor `number`.
const padNumber = (n) => String(n).padStart(3, "0");
const ticketNumbersDisplay = (row) => {
  if (row.tickets?.length) {
    const nums = row.tickets.map((t) => t.number).sort((a, b) => a - b);
    return nums.length > 1
      ? `${padNumber(nums[0])}–${padNumber(nums[nums.length - 1])}`
      : padNumber(nums[0]);
  }
  if (row.number != null) return padNumber(row.number);
  return "—";
};

// Same fallback as AdminTicketSalesPrint.vue: comps and not-yet-backfilled
// legacy rows have no door_first/door_last, so use the purchaser's name.
const doorLast = (row) => row.door_last || row.patron.last_name;
const doorFirst = (row) => row.door_first || row.patron.first_name;
const doorName = (row) => `${doorFirst(row)} ${doorLast(row)}`;

const compareNames = (a, b) =>
  a[0].localeCompare(b[0], undefined, { sensitivity: "base" }) ||
  a[1].localeCompare(b[1], undefined, { sensitivity: "base" });

// "2026-10-23 15:00:00" — sorts performances chronologically.
const performanceKey = (row) =>
  `${row.performance.date ?? ""} ${row.performance.start_time ?? ""}`;

// Lowest ticket number on a sale; sales without numbers sort last.
const firstTicketNumber = (row) => {
  if (row.tickets?.length)
    return Math.min(...row.tickets.map((t) => Number(t.number)));
  if (row.number != null) return Number(row.number);
  return Number.MAX_SAFE_INTEGER;
};

// Seats a sale still holds — quantity minus cancelled tickets. Legacy rows
// with no quantity count as one, as before.
const activeQty = (rec) =>
  rec.quantity == null
    ? 1
    : Math.max(0, Number(rec.quantity) - (Number(rec.cancelled) || 0));

// Seats a sale takes out of the performance's seat count — quantity minus
// cancelled seats that were resold (TicketSale::held_quantity). A cancelled
// seat that wasn't resold is still taken.
const heldQty = (rec) =>
  rec.quantity == null
    ? 1
    : Math.max(0, Number(rec.quantity) - (Number(rec.resold) || 0));

// Holds no seats: every ticket cancelled, or rescheduled to another
// performance (quantity 0). Kept for the record, shown greyed out.
const isFullyCancelled = (rec) => activeQty(rec) === 0;

const allTicketsRedeemed = (row) =>
  !!row.tickets?.length && row.tickets.every((t) => !!t.redeemed_at);

// Order requested: Performance Date : Name : Qty : Ticket #s : Paid :
// No Show : Cancelled — Name since split into Last/First. (Date Sold was
// dropped from the table; it's still searchable and in the export.)
// "payment_method"/"actions" (both unlabeled icon-only columns) stay
// trailing at the end.
const columns = [
  {
    name: "performance",
    label: "Performance",
    field: (row) =>
      `${row.performance.formatted_date} ${row.performance.formatted_time}`,
    sort: (_a, _b, rowA, rowB) =>
      performanceKey(rowA).localeCompare(performanceKey(rowB)),
    align: "left",
    sortable: true,
  },
  // Door Name (not the purchaser) split into Last/First, to match the box
  // office's spreadsheet and the printed door sheet
  // (AdminTicketSalesPrint.vue).
  // Each sorts by its own name, then the other as a tiebreaker.
  {
    name: "last",
    label: "Last",
    field: (row) => doorLast(row),
    sort: (_a, _b, rowA, rowB) =>
      compareNames(
        [doorLast(rowA), doorFirst(rowA)],
        [doorLast(rowB), doorFirst(rowB)],
      ),
    align: "left",
    sortable: true,
  },
  {
    name: "first",
    label: "First",
    field: (row) => doorFirst(row),
    sort: (_a, _b, rowA, rowB) =>
      compareNames(
        [doorFirst(rowA), doorLast(rowA)],
        [doorFirst(rowB), doorLast(rowB)],
      ),
    align: "left",
    sortable: true,
  },
  {
    name: "quantity",
    label: "Qty",
    field: (row) => row.quantity ?? "?",
    sort: (_a, _b, rowA, rowB) => (rowA.quantity || 0) - (rowB.quantity || 0),
    align: "center",
    sortable: true,
  },
  {
    name: "tickets",
    label: "Tickets",
    field: ticketNumbersDisplay,
    sort: (_a, _b, rowA, rowB) =>
      firstTicketNumber(rowA) - firstTicketNumber(rowB),
    align: "center",
    sortable: true,
  },
  {
    name: "confirmed",
    label: "Paid",
    field: "confirmed",
    sort: (a, b) => Number(!!a) - Number(!!b),
    align: "center",
    sortable: true,
  },
  {
    name: "cancelled",
    label: "Cancelled",
    field: "cancelled",
    sort: (a, b) => (Number(a) || 0) - (Number(b) || 0),
    align: "center",
    sortable: true,
  },
  {
    name: "resold",
    label: "Resold",
    field: "resold",
    sort: (a, b) => (Number(a) || 0) - (Number(b) || 0),
    align: "center",
    sortable: true,
  },
  {
    name: "no_show",
    label: "No Show",
    field: "no_show",
    sort: (a, b) => (Number(a) || 0) - (Number(b) || 0),
    align: "center",
    sortable: true,
  },
  {
    name: "front_row",
    label: "FR",
    field: (row) => (row.front_row > 0 ? row.front_row : ""),
    sort: (_a, _b, rowA, rowB) => (rowA.front_row || 0) - (rowB.front_row || 0),
    align: "center",
    sortable: true,
  },
  {
    name: "payment_method",
    label: "",
    field: (row) => row.payment_method?.label ?? "",
    sortable: true,
    align: "center",
    style: "width: 16px; padding: 0;",
  },
  {
    name: "actions",
    label: "",
    field: "",
    align: "center",
    style: "width: 120px;",
  },
];

const recs = computed(() => {
  if (!show.value) return [];
  return store.admin.ticket_sales.filter(
    (rec) => rec.performance.show.id == show.value.value,
  );
});

const contains = (haystack, needle) =>
  String(haystack ?? "")
    .toLowerCase()
    .includes(String(needle).trim().toLowerCase());

const yesNoMatches = (value, wanted) => (wanted === "yes" ? !!value : !value);

// Every criterion must match. Ticket numbers are per show, and the whole
// search runs inside the selected show.
const filteredRecs = computed(() => {
  const c = criteria.value;
  let result = recs.value;

  if (c.performance_id) {
    result = result.filter((rec) => rec.performance.id === c.performance_id);
  }

  if (c.payment_method_id) {
    result = result.filter(
      (rec) => rec.payment_method?.id === c.payment_method_id,
    );
  }

  // Door name (shown in the table) or purchaser (in the info popup), so a
  // search still finds a sale whose door name was overridden to someone else.
  if (c.name) {
    result = result.filter(
      (rec) =>
        contains(doorName(rec), c.name) ||
        contains(`${rec.patron.first_name} ${rec.patron.last_name}`, c.name),
    );
  }

  if (c.email) {
    result = result.filter((rec) => contains(rec.patron.email, c.email));
  }

  // Digits only, so "(099) 123-4567" finds "0991234567" and vice versa.
  if (c.phone) {
    const digits = String(c.phone).replace(/\D/g, "");
    result = result.filter((rec) =>
      digits
        ? String(rec.patron.phone ?? "")
            .replace(/\D/g, "")
            .includes(digits)
        : contains(rec.patron.phone, c.phone),
    );
  }

  // "7", "007" or "#007" matches the sale holding that ticket, including
  // one in the middle of a range like 005–009.
  if (c.ticket) {
    const num = Number(String(c.ticket).trim().replace("#", ""));
    result = result.filter(
      (rec) =>
        rec.tickets?.some((t) => Number(t.number) === num) ||
        (rec.number != null && Number(rec.number) === num),
    );
  }

  if (c.angel_level) {
    result = result.filter((rec) => rec.patron.angel_level === c.angel_level);
  }

  if (c.notes) {
    result = result.filter(
      (rec) =>
        contains(rec.special_seating, c.notes) ||
        contains(rec.comments, c.notes),
    );
  }

  if (c.sold_from) {
    result = result.filter(
      (rec) =>
        rec.sold_at &&
        format(parseISO(rec.sold_at), "yyyy-MM-dd") >= c.sold_from,
    );
  }

  if (c.sold_to) {
    result = result.filter(
      (rec) =>
        rec.sold_at && format(parseISO(rec.sold_at), "yyyy-MM-dd") <= c.sold_to,
    );
  }

  if (c.confirmed) {
    result = result.filter((rec) => yesNoMatches(rec.confirmed, c.confirmed));
  }

  if (c.no_show) {
    result = result.filter((rec) => yesNoMatches(rec.no_show, c.no_show));
  }

  if (c.cancelled) {
    result = result.filter((rec) =>
      yesNoMatches(rec.cancelled > 0, c.cancelled),
    );
  }

  if (c.front_row) {
    result = result.filter((rec) =>
      yesNoMatches(rec.front_row > 0, c.front_row),
    );
  }

  return result;
});

// Angel levels held by this show's purchasers this season.
const angelLevelOptions = computed(() =>
  sortBy(
    uniqBy(
      recs.value.filter((rec) => rec.patron.angel_level),
      (rec) => rec.patron.angel_level,
    ).map((rec) => ({
      label: rec.patron.angel_level,
      value: rec.patron.angel_level,
    })),
    "label",
  ),
);

// Distinct performances for the selected show, for the date filter — drawn
// from recs (not store.admin.shows) so it only offers dates that actually
// have ticket sales to filter down to.
const performanceOptions = computed(() => {
  const uniq = uniqBy(recs.value, (rec) => rec.performance.id).map((rec) => ({
    label: `${rec.performance.formatted_date} ${rec.performance.formatted_time}`,
    value: rec.performance.id,
    date: rec.performance.date,
  }));
  return sortBy(uniq, "date");
});

// Drawn from every sale (not just this show's) so the options — and a
// filter kept from another show — stay put when the show changes.
const paymentMethodOptions = computed(() =>
  sortBy(
    uniqBy(
      store.admin.ticket_sales.filter((rec) => rec.payment_method),
      (rec) => rec.payment_method.id,
    ).map((rec) => ({
      label: rec.payment_method.label,
      value: rec.payment_method.id,
      color: rec.payment_method.color,
    })),
    "label",
  ),
);

const shows = computed(() => {
  const fromSales = uniqBy(
    store.admin.ticket_sales,
    (rec) => rec.performance.show.id,
  ).map((rec) => ({
    label: rec.performance.show.name,
    value: rec.performance.show.id,
  }));

  const current = store.home.currentShow;
  if (current && !fromSales.some((s) => s.value === current.id)) {
    fromSales.unshift({ label: current.name, value: current.id });
  }

  return fromSales;
});

const ticketPrice = computed(
  () => recs.value[0]?.performance?.show?.ticket_price ?? 0,
);

// Comp tickets are confirmed=true from the moment they're redeemed (see
// CompTixController::redeemComp()) and stay that way; regular ticket_sales
// rows default to unconfirmed, so only exclude those where it's
// explicitly false.
const confirmedRecs = computed(() =>
  recs.value.filter((rec) => rec.confirmed !== false),
);

// A sale's revenue multiplier: its show's own rate for the payment method
// when the show has one (frozen when the show was created or the method was
// made Per Show — see ShowPaymentRate), otherwise the method's global one.
// (Each sale's show comes with its payment_rates — TicketSaleController::allSales().)
const multiplierFor = (rec) => {
  const own = rec.performance?.show?.payment_rates?.find(
    (r) => r.payment_method_id === rec.payment_method?.id,
  )?.revenue_multiplier;
  if (own != null) return Number(own);
  const global = rec.payment_method?.revenue_multiplier;
  return global == null ? 1 : Number(global);
};

// Projected across every reservation, confirmed or not (as if pending
// payments all come in) — matches the box office's projected-income
// spreadsheet and the Projected column in Tickets Sold By Date.
const revenueByMethod = computed(() => {
  const price = ticketPrice.value;
  const groups = {};
  for (const rec of recs.value) {
    const { label, color } = rec.payment_method;
    const multiplier = multiplierFor(rec);
    if (!groups[label])
      groups[label] = {
        label,
        color,
        multiplier,
        count: 0,
        revenue: 0,
      };
    const qty = activeQty(rec);
    groups[label].count += qty;
    groups[label].revenue += qty * price * multiplier;
  }
  return Object.values(groups);
});

const totalRevenue = computed(() =>
  revenueByMethod.value.reduce((sum, m) => sum + m.revenue, 0),
);

// Confirmed-only — the Confirmed total in Tickets Sold By Date.
const confirmedTicketCount = computed(() =>
  confirmedRecs.value.reduce((sum, rec) => sum + activeQty(rec), 0),
);

// Per-ticket price after a payment method's revenue multiplier (e.g. Fixr's
// fee), shown in the Projected Revenue formula: $15, $13.80, $12, $0.
const effectivePrice = (multiplier) => {
  const amount = ticketPrice.value * multiplier;
  return Number.isInteger(amount) ? amount : amount.toFixed(2);
};

// Seats taken regardless of confirmation — a pending sale still occupies a
// seat, and so does a cancelled one until it's resold.
const totalTickets = computed(() =>
  recs.value.reduce((sum, rec) => sum + heldQty(rec), 0),
);

// Each performance's own target when it overrides one, then the show's,
// otherwise the global Default Sold Out Target — same rule as
// Performance::effectiveSoldOutTarget() on the backend.
const soldOutCapacity = computed(() => {
  if (!show.value) return 0;
  const s = store.admin.shows?.find((s) => s.id === show.value.value);
  const globalTarget =
    s?.sold_out_target != null
      ? Number(s.sold_out_target)
      : Number(store.config?.sold_out_target) || 0;
  return (s?.performances ?? []).reduce(
    (sum, p) =>
      sum +
      (p.sold_out_target != null ? Number(p.sold_out_target) : globalTarget),
    0,
  );
});

// Only fetched on the Site Config page otherwise.
onMounted(() => {
  if (!store.config) store.getSiteConfig();
});

const soldOutPct = computed(() => {
  if (!soldOutCapacity.value) return 0;
  return ((totalTickets.value / soldOutCapacity.value) * 100).toFixed(1);
});

// All reservations grouped by performance date, with the confirmed subset
// broken out. Revenue is projected across every reservation (as if pending
// payments all come in), using the same price × multiplier as
// revenueByMethod. Independent of the search criteria so the summary always
// shows every date regardless of which one is searched for.
const ticketsByDate = computed(() => {
  const price = ticketPrice.value;
  const groups = {};
  for (const rec of recs.value) {
    const perf = rec.performance;
    if (!groups[perf.id]) {
      groups[perf.id] = {
        id: perf.id,
        date: perf.date,
        label: `${perf.formatted_date} ${perf.formatted_time}`,
        confirmed: 0,
        total: 0,
        revenue: 0,
      };
    }
    const qty = activeQty(rec);
    groups[perf.id].total += qty;
    if (rec.confirmed !== false) groups[perf.id].confirmed += qty;
    groups[perf.id].revenue += qty * price * multiplierFor(rec);
  }
  return sortBy(Object.values(groups), "date");
});

// PayPal and Bank Transfer are the only methods that sit pending until the
// box office sees the money arrive — this is the box office's "who to
// chase" list. Covers the show picked in the selector (so stragglers from a
// show that just closed can still be chased), but ignores the
// search/performance/payment filters.
const PENDING_PAYMENT_METHODS = ["paypal", "transfer"];
const unconfirmedDialog = ref(false);

const unconfirmedRecs = computed(() => {
  if (!show.value) return [];
  return sortBy(
    store.admin.ticket_sales.filter(
      (rec) =>
        rec.performance.show.id == show.value.value &&
        !rec.confirmed &&
        !isFullyCancelled(rec) &&
        PENDING_PAYMENT_METHODS.includes(rec.payment_method?.value),
    ),
    [
      (rec) => rec.performance.date,
      (rec) => rec.performance.start_time,
      (rec) => rec.patron.last_name?.toLowerCase(),
    ],
  );
});

// Unconfirmed Cash and Door sales are mostly walk-ups with no one to chase,
// so the dialog shows them together as one totals line rather than per
// purchaser, and they stay out of the reminder selection.
const CASH_DOOR_METHODS = ["cash", "door"];
const unconfirmedCash = computed(() => {
  const sales = show.value
    ? store.admin.ticket_sales.filter(
        (rec) =>
          rec.performance.show.id == show.value.value &&
          !rec.confirmed &&
          !isFullyCancelled(rec) &&
          CASH_DOOR_METHODS.includes(rec.payment_method?.value),
      )
    : [];
  const byPerformance = {};
  for (const rec of sales) {
    const perf = rec.performance;
    byPerformance[perf.id] ??= {
      id: perf.id,
      date: perf.date,
      start_time: perf.start_time,
      formatted_date: perf.formatted_date,
      formatted_time: perf.formatted_time,
      sales: 0,
      qty: 0,
    };
    byPerformance[perf.id].sales += 1;
    byPerformance[perf.id].qty += activeQty(rec);
  }
  return {
    sales: sales.length,
    qty: sales.reduce((sum, rec) => sum + activeQty(rec), 0),
    byPerformance: sortBy(Object.values(byPerformance), ["date", "start_time"]),
  };
});
const cashDoorDialog = ref(false);

// Checked rows to remind — everyone by default each time the list opens.
const reminderSelected = ref([]);
watch(unconfirmedDialog, (open) => {
  if (open) reminderSelected.value = unconfirmedRecs.value.map((r) => r.id);
});

const reminderAllState = computed(() => {
  if (reminderSelected.value.length === 0) return false;
  if (reminderSelected.value.length === unconfirmedRecs.value.length) return true;
  return null;
});

const toggleAllReminders = (val) => {
  reminderSelected.value = val ? unconfirmedRecs.value.map((r) => r.id) : [];
};

// The server sends one email per patron, so a patron with two pending
// sales counts once.
const reminderPatronCount = computed(
  () =>
    new Set(
      unconfirmedRecs.value
        .filter((r) => reminderSelected.value.includes(r.id))
        .map((r) => r.patron.id),
    ).size,
);

const reminderDialog = ref(false);
const reminderSending = ref(false);
const reminderSubject = ref("");
const reminderBody = ref(
  "We have your reservation, but we haven't received your payment yet. " +
    "If you've already sent it, thank you! Please reply and let us know " +
    "when and how it was sent so we can match it up.",
);

// The default subject names the selected show. It's refreshed on open
// unless the admin has typed their own, so switching shows doesn't leave
// the previous show's name in it.
let autoReminderSubject = "";
const openReminderDialog = () => {
  if (!reminderSubject.value || reminderSubject.value === autoReminderSubject) {
    autoReminderSubject = `Payment Reminder - ${show.value?.label ?? ""}`;
    reminderSubject.value = autoReminderSubject;
  }
  reminderDialog.value = true;
};

const sendReminders = async () => {
  reminderSending.value = true;
  try {
    const response = await callApi({
      path: "/ticket-sales/payment-reminders",
      method: "post",
      useAuth: true,
      payload: {
        ticket_sale_ids: reminderSelected.value,
        subject: reminderSubject.value,
        body: reminderBody.value,
      },
    });

    if (!response || response.status !== "success") {
      Notify.create({
        type: "negative",
        message: response?.message || "Something went wrong.",
      });
      return;
    }

    if (response.failed?.length) {
      Notify.create({
        type: "warning",
        timeout: 0,
        actions: [{ label: "Dismiss", color: "white" }],
        message: `Sent ${response.sent}, but these failed: ${response.failed.join(", ")}`,
      });
    } else {
      Notify.create({
        type: "positive",
        message: `Sent ${response.sent} reminder${response.sent === 1 ? "" : "s"}.`,
      });
    }
    reminderDialog.value = false;
  } finally {
    reminderSending.value = false;
  }
};

const projectedRevenueAll = computed(() =>
  ticketsByDate.value.reduce((sum, row) => sum + row.revenue, 0),
);

const paymentMethodColors = computed(() => {
  const methods = uniqBy(recs.value, (rec) => rec.payment_method.id).map(
    (rec) => rec.payment_method,
  );
  return Object.fromEntries(methods.map((m) => [m.label, m.color]));
});

const onDelete = async (row) => {
  Dialog.create({
    title: "Delete Ticket Sale",
    message: `Delete the sale to ${row.patron.first_name} ${row.patron.last_name} for the ${row.performance.formatted_date} performance at ${row.performance.formatted_time}? Only delete a sale entered by mistake or for testing — if the patron cancelled, set the Cancelled count instead so the record is kept.`,
    ok: "Yes",
    cancel: "No",
  }).onOk(async () => {
    const response = await callApi({
      path: `/ticket-sales`,
      method: "delete",
      useAuth: true,
      payload: row,
    });
    store.admin.ticket_sales = response;
  });
};

const onNewTicket = async () => {
  store.router.push({
    name: "admin-ticket-sale-new",
    params: { show_id: show.value.value },
  });
};

const onEditSale = async (row) => {
  store.router.push({
    name: "admin-ticket-sale-edit",
    params: { id: row.id },
  });
};

// Flex and comp tickets can go back to the patron when cancelled — the
// admin is asked each time more of them are cancelled.
const RETURNABLE_METHODS = ["flex", "comp"];

const askReturn = (row, newlyCancelled) =>
  new Promise((resolve) => {
    const name = `${row.patron.first_name} ${row.patron.last_name}`.trim();
    const isComp = row.payment_method?.value === "comp";
    const tickets = `${newlyCancelled} ticket${newlyCancelled === 1 ? "" : "s"}`;

    Dialog.create({
      title: isComp ? "Return the comp?" : "Return to Flex balance?",
      message: isComp
        ? `Give ${name} their comp back, so they can use it for another performance? If not, the comp stays used.`
        : `Return the ${tickets} just cancelled to ${name}'s Flex balance? If not, ${newlyCancelled === 1 ? "it stays" : "they stay"} counted as used.`,
      persistent: true,
      ok: {
        label: isComp ? "Return comp" : "Return to balance",
        color: "primary",
        flat: true,
      },
      cancel: { label: "Don't return", flat: true },
    })
      .onOk(() => resolve(true))
      .onCancel(() => resolve(false));
  });

// How many of the sale's tickets were cancelled — clamped to 0..quantity,
// saved at once, and put back if the save fails. The server also caps No
// Show to the seats left and works out how many cancelled tickets were
// returned, so those come from the response.
const updateCancelled = async (row, value) => {
  const max = Number(row.quantity) || 1;
  const count = Math.min(max, Math.max(0, Math.floor(Number(value) || 0)));
  const previous = {
    cancelled: row.cancelled,
    cancelled_returned: row.cancelled_returned,
    resold: row.resold,
    no_show: row.no_show,
  };
  const newlyCancelled = count - (Number(row.cancelled) || 0);
  if (newlyCancelled === 0) return;

  row.cancelled = count;

  const payload = { cancelled: count };
  if (
    newlyCancelled > 0 &&
    RETURNABLE_METHODS.includes(row.payment_method?.value)
  ) {
    payload.return = await askReturn(row, newlyCancelled);
  }

  const response = await callApi({
    path: `/ticket-sales/cancelled/${row.id}`,
    method: "put",
    payload,
    useAuth: true,
    showError: false,
  }).catch(() => null);

  if (!response || response.status !== "success") {
    Object.assign(row, previous);
    Notify.create({
      type: "negative",
      message: response?.message || "Couldn't update Cancelled.",
    });
    return;
  }

  row.no_show = response.no_show;
  row.cancelled_returned = response.cancelled_returned;
  row.resold = response.resold;
  row.active_quantity = Math.max(
    0,
    (Number(row.quantity) || 0) - response.cancelled,
  );
  row.used_quantity = Math.max(
    0,
    (Number(row.quantity) || 0) - response.cancelled_returned,
  );
};

// How many cancelled seats were put back on sale — clamped to
// 0..cancelled, saved at once, and put back if the save fails.
const updateResold = async (row, value) => {
  const max = Number(row.cancelled) || 0;
  const count = Math.min(max, Math.max(0, Math.floor(Number(value) || 0)));
  const previous = row.resold;
  if (count === (Number(previous) || 0)) return;
  row.resold = count;

  const response = await callApi({
    path: `/ticket-sales/resold/${row.id}`,
    method: "put",
    payload: { resold: count },
    useAuth: true,
    showError: false,
  }).catch(() => null);

  if (!response || response.status !== "success") {
    row.resold = previous;
    Notify.create({
      type: "negative",
      message: response?.message || "Couldn't update Resold.",
    });
  }
};

// How many of the party didn't show — clamped to 0..quantity, saved at
// once, and put back if the save fails.
const updateNoShow = async (row, value) => {
  const max = activeQty(row);
  const count = Math.min(max, Math.max(0, Math.floor(Number(value) || 0)));
  const previous = row.no_show;
  row.no_show = count;

  const response = await callApi({
    path: `/ticket-sales/no-show/${row.id}`,
    method: "put",
    payload: { no_show: count },
    useAuth: true,
  });

  if (!response || response.status !== "success") {
    row.no_show = previous;
    Notify.create({
      type: "negative",
      message: response?.message || "Couldn't update No Show.",
    });
  }
};

// Optimistic: flips the row at once (so totals and the Pending list
// update), and flips it back if the save fails.
const updatePaid = async (row, value) => {
  const previous = row.confirmed;
  row.confirmed = value;

  const response = await callApi({
    path: `/ticket-sales/paid/${row.id}`,
    method: "put",
    payload: { confirmed: value },
    useAuth: true,
  });

  if (!response || response.status !== "success") {
    row.confirmed = previous;
    Notify.create({
      type: "negative",
      message: response?.message || "Couldn't update Paid.",
    });
  }
};

// Reschedule: moves a purchase to another performance of the same show.
// The server copies it there (with its ticket numbers) and keeps the
// original at quantity 0 — see TicketSaleController::reschedule().
const rescheduleDialog = ref(false);
const rescheduleRow = ref(null);
const rescheduleTo = ref(null);
const rescheduleNote = ref("");
const rescheduleLoading = ref(false);
const rescheduleSaving = ref(false);
const reschedulePerformances = ref([]);

const rescheduleOptions = computed(() => {
  const today = format(new Date(), "yyyy-MM-dd");
  return reschedulePerformances.value.map((p) => {
    const isCurrent = p.id === rescheduleRow.value?.performance.id;
    const isPast = String(p.date).slice(0, 10) < today;
    const notes = [
      isCurrent && "current",
      isPast && "past",
      p.sold_out && "sold out",
    ]
      .filter(Boolean)
      .join(", ");
    return {
      label: `${p.formatted_date} ${p.formatted_time}${notes ? ` (${notes})` : ""}`,
      value: p.id,
      disable: isCurrent,
    };
  });
});

const openReschedule = async (row) => {
  rescheduleRow.value = row;
  rescheduleTo.value = null;
  rescheduleNote.value = "";
  rescheduleDialog.value = true;

  rescheduleLoading.value = true;
  try {
    const showData = await callApi({
      path: `/shows/${row.performance.show.id}`,
      method: "get",
      useAuth: true,
    });
    reschedulePerformances.value = sortBy(showData?.performances ?? [], [
      "date",
      "start_time",
    ]);
  } finally {
    rescheduleLoading.value = false;
  }
};

const saveReschedule = async () => {
  rescheduleSaving.value = true;
  try {
    const response = await callApi({
      path: `/ticket-sales/${rescheduleRow.value.id}/reschedule`,
      method: "post",
      payload: {
        performance_id: rescheduleTo.value,
        note: rescheduleNote.value || null,
      },
      useAuth: true,
    }).catch(() => null);

    // false = the server refused with a message, which callApi already
    // showed; null = the request itself failed.
    if (response === false) return;
    if (!response || response.status !== "success") {
      Notify.create({ type: "negative", message: "Couldn't reschedule." });
      return;
    }

    store.admin.ticket_sales = response.sales;
    rescheduleDialog.value = false;
    Notify.create({ type: "positive", message: "Rescheduled." });
  } finally {
    rescheduleSaving.value = false;
  }
};

const ticketsDialog = ref(false);
const ticketsDialogRows = ref([]);
const activeTicketSaleId = ref(null);

const openTicketsDialog = (row) => {
  activeTicketSaleId.value = row.id;
  ticketsDialogRows.value = [...row.tickets]
    .sort((a, b) => a.number - b.number)
    .map((t) => ({ ...t, redeemed: !!t.redeemed_at }));
  ticketsDialog.value = true;
};

const saveTicketRedemptions = async () => {
  const response = await callApi({
    path: `/ticket-sales/${activeTicketSaleId.value}/tickets`,
    method: "put",
    useAuth: true,
    payload: {
      tickets: ticketsDialogRows.value.map((t) => ({ id: t.id, redeemed: t.redeemed })),
    },
  });

  if (!response || response.status !== "success") {
    Notify.create({
      type: "negative",
      message: response?.message || "Something went wrong.",
    });
    return;
  }

  const sale = store.admin.ticket_sales.find((s) => s.id === activeTicketSaleId.value);
  if (sale) sale.tickets = response.tickets;

  Notify.create({ type: "positive", message: "Ticket redemptions saved." });
  ticketsDialog.value = false;
};
</script>

<style scoped>
.tsbd-col {
  min-width: 70px;
  align-items: flex-end;
}
/* Every ticket on the sale cancelled — kept for the record, greyed out. */
:deep(tr.row-cancelled td) {
  color: #9e9e9e;
}
:deep(tr.row-cancelled td:nth-child(2)),
:deep(tr.row-cancelled td:nth-child(3)) {
  text-decoration: line-through;
}
.tsbd-rev {
  min-width: 90px;
  align-items: flex-end;
}
</style>
