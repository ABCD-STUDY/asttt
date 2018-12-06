To make this work:

- create virtualenv with jinja2, pycap
- since cron.sh just executes the action, full path to virtualenv in the shebang
- each script needs to be able to decode a JSONified $1
- pipe the result of python/render_jinja.py to sendEmail.sh
- Optional: possibly need to package the Fitabase API wrapper
  - Alternative: start pushing Fitabase API data to Redcap, so no need 
  here
