<div class="pc-wrap">
  <style>
    .pc-wrap {
      max-width: 820px;
      margin: 0 auto;
      padding: 24px 16px 64px;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      color: #2a255e;
    }
    .pc-wrap .pc-header {
      text-align: center;
      margin-bottom: 28px;
    }
    .pc-wrap .pc-header h1 {
      color: #2a255e;
      font-size: 28px;
      margin: 0 0 4px;
    }
    .pc-wrap .pc-header p {
      color: #b08d57;
      font-style: italic;
      margin: 0;
    }
    .pc-wrap .pc-section {
      background: #efeffb;
      border: 1px solid #c4c9e5;
      border-radius: 6px;
      padding: 18px 20px;
      margin-bottom: 18px;
    }
    .pc-wrap .pc-section h2 {
      color: #7e79b8;
      font-size: 17px;
      margin: 0 0 14px;
    }
    .pc-wrap .pc-field {
      margin-bottom: 14px;
    }
    .pc-wrap .pc-field:last-child {
      margin-bottom: 0;
    }
    .pc-wrap label {
      display: block;
      font-weight: 600;
      font-size: 13px;
      margin-bottom: 4px;
    }
    .pc-wrap .pc-hint {
      display: block;
      font-weight: 400;
      font-style: italic;
      font-size: 12px;
      color: #666;
      margin-bottom: 6px;
    }
    .pc-wrap input[type="text"],
    .pc-wrap input[type="date"],
    .pc-wrap input[type="email"],
    .pc-wrap textarea {
      width: 100%;
      box-sizing: border-box;
      padding: 8px 10px;
      font-size: 14px;
      font-family: inherit;
      border: 1px solid #c4c9e5;
      border-radius: 4px;
      background: #fff;
      color: #2a255e;
    }
    .pc-wrap textarea {
      resize: vertical;
      min-height: 70px;
    }
    .pc-wrap .pc-row {
      display: flex;
      gap: 14px;
      flex-wrap: wrap;
    }
    .pc-wrap .pc-row > div {
      flex: 1;
      min-width: 200px;
    }
    .pc-wrap .pc-checkbox-group label {
      display: flex;
      align-items: center;
      font-weight: 400;
      font-size: 14px;
      margin-bottom: 6px;
    }
    .pc-wrap .pc-checkbox-group input {
      margin-right: 8px;
      width: auto;
    }
    .pc-wrap .pc-milestone,
    .pc-wrap .pc-risk-row {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 8px;
    }
    .pc-wrap .pc-milestone span {
      flex: 1;
      font-size: 14px;
    }
    .pc-wrap .pc-milestone input {
      flex: 0 0 180px;
    }
    .pc-wrap .pc-risk-row input {
      flex: 1;
    }
    .pc-wrap .pc-risk-row .pc-risk-num {
      flex: 0 0 20px;
      color: #7e79b8;
      font-weight: 600;
    }
    .pc-wrap .pc-measure-row {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 8px;
    }
    .pc-wrap .pc-measure-row span {
      flex: 0 0 16px;
      color: #7e79b8;
      font-weight: 600;
    }
    .pc-wrap .pc-submit-row {
      text-align: right;
      margin-top: 20px;
    }
    .pc-wrap button[type="submit"] {
      background: #7e79b8;
      color: #fff;
      border: none;
      border-radius: 4px;
      padding: 12px 28px;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
    }
    .pc-wrap button[type="submit"]:disabled {
      opacity: 0.6;
      cursor: default;
    }
    .pc-wrap .pc-error {
      color: #c0392b;
      font-size: 13px;
      margin-top: 10px;
      display: none;
    }
    .pc-wrap .pc-thankyou {
      text-align: center;
      padding: 60px 20px;
    }
    .pc-wrap .pc-thankyou h2 {
      color: #7e79b8;
    }
  </style>

  <div class="pc-header">
    <h1>ACT Project Charter</h1>
    <p>Together we imagine. Together we create. Together we ACT.</p>
  </div>

  <form id="pc-form">
    <div class="pc-section">
      <div class="pc-row">
        <div class="pc-field">
          <label for="pc-title">Project Title</label>
          <input type="text" id="pc-title" name="title" required>
        </div>
        <div class="pc-field">
          <label for="pc-sponsor">Project Sponsor</label>
          <input type="text" id="pc-sponsor" name="sponsor" required>
        </div>
      </div>
    </div>

    <div class="pc-section">
      <h2>1. Project Overview</h2>
      <div class="pc-field">
        <label for="pc-description">Project Description
          <span class="pc-hint">Brief summary (3-5 sentences) of the project activities and intended impact.</span>
        </label>
        <textarea id="pc-description" name="description" rows="4" required></textarea>
      </div>
    </div>

    <div class="pc-section">
      <h2>2. Impact &amp; Beneficiaries</h2>
      <div class="pc-field">
        <label for="pc-importance">Why Is This Project Important?
          <span class="pc-hint">Describe the problem, challenge, or opportunity this project addresses.</span>
        </label>
        <textarea id="pc-importance" name="importance" rows="4"></textarea>
      </div>
    </div>

    <div class="pc-section">
      <h2>3. Project Goals &amp; Success Measures</h2>
      <div class="pc-field">
        <label for="pc-goal">Project Goal
          <span class="pc-hint">What positive change do you hope to create?</span>
        </label>
        <textarea id="pc-goal" name="goal" rows="3"></textarea>
      </div>
      <div class="pc-field">
        <label>What Does Success Look Like?
          <span class="pc-hint">List 3-5 specific, measurable outcomes.</span>
        </label>
        @for ($i = 0; $i < 5; $i++)
          <div class="pc-measure-row">
            <span>{{ $i + 1 }}.</span>
            <input type="text" name="success_measures[]">
          </div>
        @endfor
      </div>
      <div class="pc-field pc-checkbox-group">
        <label>How Will Success Be Measured?</label>
        @foreach ([
          'Attendance Records / Audience Growth',
          'Surveys / Feedback Forms',
          'Interviews / Testimonials',
          'Photos / Videos',
          'Observation',
          'Social Media Metrics',
          'Financial Sustainability',
        ] as $option)
          <label><input type="checkbox" name="measurement_methods[]" value="{{ $option }}"> {{ $option }}</label>
        @endforeach
      </div>
      <div class="pc-field">
        <label for="pc-measurement-approach">Describe Your Measurement Approach</label>
        <textarea id="pc-measurement-approach" name="measurement_approach" rows="3"></textarea>
      </div>
    </div>

    <div class="pc-section">
      <h2>4. Project Plan &amp; Timeline</h2>
      <div class="pc-row">
        <div class="pc-field">
          <label for="pc-start-date">Project Start Date</label>
          <input type="date" id="pc-start-date" name="start_date">
        </div>
        <div class="pc-field">
          <label for="pc-completion-date">Project Completion Date</label>
          <input type="date" id="pc-completion-date" name="completion_date">
        </div>
      </div>
      <div class="pc-field">
        <label>Key Milestones &amp; Target Dates</label>
        @foreach ([
          'planning_completed' => 'Project Planning Completed',
          'partners_confirmed' => 'Partners / Volunteers Confirmed',
          'launch' => 'Project Launch',
          'main_activity' => 'Main Activity / Event',
          'evaluation' => 'Evaluation & Reflection',
          'final_report' => 'Final Report Submitted',
        ] as $key => $label)
          <div class="pc-milestone">
            <span>{{ $label }}</span>
            <input type="date" name="milestones[{{ $key }}]">
          </div>
        @endforeach
      </div>
    </div>

    <div class="pc-section">
      <h2>5. Resources &amp; Partnerships</h2>
      <div class="pc-field">
        <label for="pc-team-members">Team Members / Volunteers
          <span class="pc-hint">Who will help you?</span>
        </label>
        <textarea id="pc-team-members" name="team_members" rows="3"></textarea>
      </div>
      <div class="pc-field">
        <label for="pc-community-partners">Community Partners (if applicable)
          <span class="pc-hint">Schools, NGOs, community groups, local government, businesses, etc.</span>
        </label>
        <textarea id="pc-community-partners" name="community_partners" rows="3"></textarea>
      </div>
      <div class="pc-field pc-checkbox-group">
        <label>Resources Needed</label>
        @foreach ([
          'Volunteers',
          'Meeting / Event Space',
          'Educational Materials',
          'Technology / Internet',
          'Transportation',
          'Food / Refreshments',
          'Printing / Supplies',
          'Funding / Donations',
        ] as $option)
          <label><input type="checkbox" name="resources_needed[]" value="{{ $option }}"> {{ $option }}</label>
        @endforeach
      </div>
      <div class="pc-field">
        <label for="pc-resources-other">Other Resources (if any)</label>
        <input type="text" id="pc-resources-other" name="resources_other">
      </div>
    </div>

    <div class="pc-section">
      <h2>6. Documentation &amp; Storytelling</h2>
      <div class="pc-field pc-checkbox-group">
        <label>How Will You Document Your Project Journey &amp; Impact?</label>
        @foreach ([
          'Photos',
          'Videos',
          'Participant Testimonials',
          'Attendance Records',
          'Social Media Posts',
          'Media Coverage',
          'Reflection Journal',
        ] as $option)
          <label><input type="checkbox" name="documentation_methods[]" value="{{ $option }}"> {{ $option }}</label>
        @endforeach
      </div>
      <div class="pc-field">
        <label for="pc-documentation-plan">Describe Your Documentation Plan</label>
        <textarea id="pc-documentation-plan" name="documentation_plan" rows="3"></textarea>
      </div>
    </div>

    <div class="pc-section">
      <h2>7. Risks &amp; Challenges</h2>
      <p class="pc-hint">What obstacles might impact your project, and how will you address them?</p>
      <div class="pc-field">
        <div class="pc-risk-row">
          <span class="pc-risk-num"></span>
          <label style="flex:1;">Potential Challenge</label>
          <label style="flex:1;">Mitigation Strategy</label>
        </div>
        @for ($i = 0; $i < 5; $i++)
          <div class="pc-risk-row">
            <span class="pc-risk-num">{{ $i + 1 }}.</span>
            <input type="text" name="risks[{{ $i }}][challenge]" placeholder="Challenge">
            <input type="text" name="risks[{{ $i }}][mitigation]" placeholder="Mitigation">
          </div>
        @endfor
      </div>
    </div>

    <div class="pc-section">
      <h2>8. Leadership Reflection</h2>
      <div class="pc-field">
        <label for="pc-leadership-reflection">How does this project connect to the growth and development of ACT Theater Company?</label>
        <textarea id="pc-leadership-reflection" name="leadership_reflection" rows="4"></textarea>
      </div>
    </div>

    <div class="pc-section">
      <h2>Sponsor Commitment</h2>
      <p class="pc-hint">I commit to leading this project with integrity, accountability, and respect for the community I serve.</p>
      <div class="pc-row">
        <div class="pc-field">
          <label for="pc-commitment-signature">Signature (type your full name)</label>
          <input type="text" id="pc-commitment-signature" name="commitment_signature">
        </div>
        <div class="pc-field">
          <label for="pc-commitment-date">Date</label>
          <input type="date" id="pc-commitment-date" name="commitment_date">
        </div>
      </div>
    </div>

    <div class="pc-submit-row">
      <div class="pc-error" id="pc-error"></div>
      <button type="submit" id="pc-submit">Submit Charter</button>
    </div>
  </form>

  <div class="pc-thankyou" id="pc-thankyou" style="display: none;">
    <h2>Thank You!</h2>
    <p>Your project charter has been submitted. Every step forward builds our future — together we ACT.</p>
  </div>

  <script>
    (function () {
      const form = document.getElementById("pc-form");
      const errorBox = document.getElementById("pc-error");
      const submitBtn = document.getElementById("pc-submit");

      form.addEventListener("submit", async function (event) {
        event.preventDefault();
        errorBox.style.display = "none";
        submitBtn.disabled = true;
        submitBtn.textContent = "Submitting…";

        try {
          const response = await fetch("/api/project-charter", {
            method: "POST",
            body: new FormData(form),
            headers: { Accept: "application/json" },
          });
          const data = await response.json();

          if (!response.ok || data.status !== "success") {
            throw new Error(data.message || "Something went wrong. Please check the form and try again.");
          }

          form.style.display = "none";
          document.getElementById("pc-thankyou").style.display = "block";
        } catch (err) {
          errorBox.textContent = err.message || "Something went wrong. Please try again.";
          errorBox.style.display = "block";
          submitBtn.disabled = false;
          submitBtn.textContent = "Submit Charter";
        }
      });
    })();
  </script>
</div>
