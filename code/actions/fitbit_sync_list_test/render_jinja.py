import datetime
from jinja2 import Environment, FileSystemLoader
import email.utils
import os
import pandas as pd

class ApprisePandasToEmail:
    """
    Simple abstraction over a Jinja2 template rendering to pass to sendEmail.sh

    TODO: Need to properly research how pandas DataFrame styling works, if 
    .to_html conversion is the way to go, etc.

    See: https://pandas.pydata.org/pandas-docs/stable/style.html
    """
    def __init__(self, table, convert_table=False, convert_kwargs={}):
        # assert isinstance(table, pd.DataFrame)
        self.main_table = table
        if convert_table:
            self.main_table = table.to_html(**convert_kwargs)

    def render_email(self, content):
        assert 'title' in content
        if 'current_time' not in content:
            content['current_time'] = email.utils.formatdate(localtime=True)
        content['main_table'] = self.main_table

        env = Environment(loader=FileSystemLoader(
            os.path.dirname(os.path.realpath(__file__))))
        template = env.get_template('email_template.html.j2')
        return template.render(content)
