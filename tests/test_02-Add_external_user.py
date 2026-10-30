# Generated from Selenium IDE
# Test name: t02 - Add external user
import pytest
import time
import json
from selenium import webdriver
from selenium.webdriver.common.action_chains import ActionChains
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support import expected_conditions
from selenium.webdriver.support.wait import WebDriverWait
from fn_switchuser import Test_fn_switchuser as Sub1

class Test_02_Add_external_user:
  def setup_method(self, method):
    self.driver = self.selectedBrowser
    self.vars = {}
  def teardown_method(self, method):
    self.driver.quit()

  def test_02_Add_external_user(self):
    self.driver.get("http://127.0.0.1/")
    self.vars["username"] = "user1"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_switchuser() # Run fn switchuser
    time.sleep(1)
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"prefix=user_management_wizard\"]").click()
    self.driver.find_element(By.ID, "sectionExternal").click()
    self.driver.find_element(By.CSS_SELECTOR, "#sectionExternal [name=\"firstname\"]").send_keys("External")
    self.driver.find_element(By.CSS_SELECTOR, "#sectionExternal [name=\"lastname\"]").send_keys("User")
    self.driver.find_element(By.CSS_SELECTOR, "#sectionExternal [name=\"email\"]").send_keys("1@external.example.com")
    self.driver.find_element(By.CSS_SELECTOR, "#sectionExternal button").click()
    self.driver.execute_script("//SETDESC:Assert user selection table displayed")
    self.driver.find_element(By.CSS_SELECTOR, ".mod-umw-table").send_keys("SAVESCREENSHOT")
    assert len(self.driver.find_elements(By.XPATH, "//*[@class='mod-umw-table']//td[3][contains(.,'User')]")) > 0
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"page=wizard_create_user\"]").click()
    self.driver.execute_script("//SETDESC:Assert user details prefilled")
    self.driver.find_element(By.CSS_SELECTOR, "#formContainer table").send_keys("SAVESCREENSHOT")
    assert self.driver.find_element(By.CSS_SELECTOR, "input[name=\"firstname\"]").get_attribute("value") == "External"
    assert self.driver.find_element(By.CSS_SELECTOR, "input[name=\"lastname\"]").get_attribute("value") == "User"
    assert self.driver.find_element(By.CSS_SELECTOR, "input[name=\"email\"]").get_attribute("value") == "1@external.example.com"
    assert self.driver.find_element(By.CSS_SELECTOR, "input[name=\"username\"]").get_attribute("value") == "euser"
    self.driver.find_element(By.CSS_SELECTOR, "input[name=\"email\"]").send_keys("1@internal.example.com")
    self.driver.execute_script("//SETDESC:Click outside of field")
    self.driver.find_element(By.CSS_SELECTOR, "#formContainer").click()
    time.sleep(0.5)
    self.driver.find_element(By.CSS_SELECTOR, "#formContainer button").click()
    self.driver.execute_script("//SAVEDESC:Assert internal email address not accepted")
    assert len(self.driver.find_elements(By.XPATH, "//h3[contains(.,'User Project Assignment')]")) == 0
    self.driver.find_element(By.CSS_SELECTOR, "input[name=\"email\"]").send_keys("1@external.example.com")
    self.driver.find_element(By.CSS_SELECTOR, "input[name=\"username\"]").send_keys("internal1")
    self.driver.execute_script("//SETDESC:Click outside of field")
    self.driver.find_element(By.CSS_SELECTOR, "#formContainer").click()
    time.sleep(0.5)
    self.driver.find_element(By.CSS_SELECTOR, "#formContainer button").click()
    self.driver.execute_script("//SAVEDESC:Assert internal username not accepted")
    assert len(self.driver.find_elements(By.XPATH, "//h3[contains(.,'User Project Assignment')]")) == 0
    self.driver.find_element(By.CSS_SELECTOR, "input[name=\"username\"]").send_keys("external1")
    self.driver.execute_script("//SETDESC:Click outside of field")
    self.driver.find_element(By.CSS_SELECTOR, "#formContainer").click()
    time.sleep(0.5)
    self.driver.find_element(By.CSS_SELECTOR, "#formContainer button").click()
    self.driver.execute_script("//SETDESC:Assert account created")
    self.driver.find_element(By.XPATH, "//h3[contains(.,'User Project Assignment')]").send_keys("SAVESCREENSHOT")
    assert len(self.driver.find_elements(By.XPATH, "//h3[contains(.,'User Project Assignment')]")) > 0
